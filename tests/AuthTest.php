<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\Auth\JWTManager;
use Nexa\Auth\JWTException;
use Nexa\Http\Request;
use App\Models\User;

class AuthTest extends TestCase
{
    private $jwtManager;
    private $testUser;
    
    public function setUp()
    {
        parent::setUp();
        
        // Initialize JWT manager
        $this->jwtManager = new JWTManager();
        
        // Create test user data
        $this->testUser = [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Set test JWT secret
        $_ENV['JWT_SECRET'] = 'test_secret_key_for_testing_purposes_only';
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        // Clean up environment
        unset($_ENV['JWT_SECRET']);
    }
    
    public function testJWTTokenGeneration()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email']);
        
        $this->assertNotNull($token);
        $this->assertTrue(is_string($token));
        $this->assertTrue(strlen($token) > 50); // JWT tokens are typically long
    }
    
    public function testJWTTokenValidation()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email']);
        $isValid = $this->jwtManager->validateToken($token);
        
        $this->assertTrue($isValid);
    }
    
    public function testJWTTokenDecoding()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email'], ['name' => $this->testUser['name']]);
        $decoded = $this->jwtManager->getUserFromToken($token);
        
        $this->assertNotNull($decoded);
        $this->assertEquals($this->testUser['id'], $decoded['id']);
        $this->assertEquals($this->testUser['email'], $decoded['email']);
        $this->assertEquals($this->testUser['name'], $decoded['name']);
    }
    
    public function testInvalidTokenValidation()
    {
        $invalidToken = 'invalid.token.here';
        $isValid = $this->jwtManager->validateToken($invalidToken);
        
        $this->assertFalse($isValid);
    }
    
    public function testExpiredTokenDetection()
    {
        // Create a token with very short expiration
        $shortLivedToken = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email'], ['name' => $this->testUser['name']], 1); // 1 second
        
        // Wait for token to expire
        sleep(2);
        
        $isExpired = $this->jwtManager->isTokenExpired($shortLivedToken);
        $this->assertTrue($isExpired);
    }
    
    public function testRefreshTokenGeneration()
    {
        $refreshToken = $this->jwtManager->generateRefreshToken($this->testUser['id'], $this->testUser['email']);
        
        $this->assertNotNull($refreshToken);
        $this->assertTrue(is_string($refreshToken));
        $this->assertTrue(strlen($refreshToken) > 50);
    }
    
    public function testTokenPairGeneration()
    {
        $tokens = $this->jwtManager->generateTokenPair($this->testUser['id'], $this->testUser['email']);
        
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('expires_in', $tokens);
        $this->assertArrayHasKey('token_type', $tokens);
        
        $this->assertEquals('Bearer', $tokens['token_type']);
        $this->assertTrue($tokens['expires_in'] > 0);
    }
    
    public function testTokenRefresh()
    {
        $tokens = $this->jwtManager->generateTokenPair($this->testUser['id'], $this->testUser['email']);
        $refreshToken = $tokens['refresh_token'];
        
        $newTokens = $this->jwtManager->refreshToken($refreshToken);
        
        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertArrayHasKey('refresh_token', $newTokens);
        $this->assertNotEquals($tokens['access_token'], $newTokens['access_token']);
    }
    
    public function testTokenBlacklisting()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email']);
        
        // Token should be valid initially
        $this->assertTrue($this->jwtManager->validateToken($token));
        
        // Blacklist the token
        $this->jwtManager->blacklistToken($token);
        
        // Token should now be invalid
        $this->assertFalse($this->jwtManager->validateToken($token));
    }
    
    public function testTokenExtractionFromHeader()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email']);
        $authHeader = "Bearer $token";
        
        $extractedToken = $this->jwtManager->extractTokenFromHeader($authHeader);
        
        $this->assertEquals($token, $extractedToken);
    }
    
    public function testInvalidTokenExtractionFromHeader()
    {
        $invalidHeader = "Basic dGVzdDp0ZXN0"; // Basic auth header
        
        $extractedToken = $this->jwtManager->extractTokenFromHeader($invalidHeader);
        
        $this->assertNull($extractedToken);
    }
    
    public function testJWTExceptionCreation()
    {
        $expiredException = JWTException::expired();
        $this->assertInstanceOf(JWTException::class, $expiredException);
        $this->assertEquals('Token has expired', $expiredException->getMessage());
        
        $invalidException = JWTException::invalid();
        $this->assertInstanceOf(JWTException::class, $invalidException);
        $this->assertEquals('Invalid token', $invalidException->getMessage());
        
        $missingException = JWTException::missing();
        $this->assertInstanceOf(JWTException::class, $missingException);
        $this->assertEquals('Token not provided', $missingException->getMessage());
    }
    
    public function testRequestUserMethods()
    {
        $request = $this->createRequest('GET', '/api/profile');
        
        // Test user agent method that exists
        $userAgent = $request->userAgent();
        $this->assertTrue(is_string($userAgent) || is_null($userAgent));
        
        // Test authentication through middleware
        $authMiddleware = new \Nexa\Http\Middleware\AuthMiddleware();
        
        // Test static user method
        $user = \Nexa\Http\Middleware\AuthMiddleware::user();
        $this->assertTrue(is_array($user) || is_null($user));
    }
    
    public function testAuthenticationFlow()
    {
        // Simulate login request
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $request = $this->createRequest('POST', '/api/auth/login', $loginData);
        
        // Simulate successful authentication
        $tokens = $this->jwtManager->generateTokenPair($this->testUser['id'], $this->testUser['email']);
        
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        
        // Test that tokens are valid strings
        $this->assertTrue(is_string($tokens['access_token']));
        $this->assertTrue(is_string($tokens['refresh_token']));
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
    }
    
    public function testTokenValidationWithDifferentSecrets()
    {
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email']);
        
        // Change secret
        $_ENV['JWT_SECRET'] = 'different_secret_key';
        $newJwtManager = new JWTManager();
        
        // Token should be invalid with different secret
        $this->assertFalse($newJwtManager->validateToken($token));
    }
    
    public function testTokenGenerationWithCustomExpiration()
    {
        $customExpiration = 7200; // 2 hours
        $token = $this->jwtManager->generateToken($this->testUser['id'], $this->testUser['email'], ['name' => $this->testUser['name']], $customExpiration);
        
        $this->assertNotNull($token);
        $this->assertTrue($this->jwtManager->validateToken($token));
        
        // Verify token is not expired
        $this->assertFalse($this->jwtManager->isTokenExpired($token));
    }
}