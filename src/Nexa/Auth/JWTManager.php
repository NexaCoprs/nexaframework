<?php

namespace Nexa\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTManager
{
    private $secretKey;
    private $algorithm;
    private $expiration;
    private $refreshExpiration;

    public function __construct($secretKey = null, $algorithm = 'HS256', $expiration = 3600, $refreshExpiration = 604800)
    {
        $this->secretKey = $secretKey ?: $_ENV['JWT_SECRET'] ?? 'your-secret-key';
        $this->algorithm = $algorithm;
        $this->expiration = $expiration; // 1 hour
        $this->refreshExpiration = $refreshExpiration; // 1 week
    }

    /**
     * Generate a JWT token for a user
     */
    public function generateToken($userId, $email, $additionalClaims = [], $customExpiration = null)
    {
        $issuedAt = time();
        $expiration = $issuedAt + ($customExpiration ?? $this->expiration);

        $payload = array_merge([
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'sub' => $userId,
            'email' => $email,
            'name' => $additionalClaims['name'] ?? null,
            'type' => 'access'
        ], $additionalClaims);

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate a refresh token
     */
    public function generateRefreshToken($userId, $email)
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->refreshExpiration;

        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'sub' => $userId,
            'email' => $email,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate a JWT token
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;
            
            // Check if token is blacklisted
            if ($this->isTokenBlacklisted($token)) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Decode a JWT token and return payload
     */
    public function decodeToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            throw new JWTException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired($token)
    {
        try {
            $payload = $this->decodeToken($token);
            return time() > $payload['exp'];
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Get user ID from token
     */
    public function getUserIdFromToken($token)
    {
        $payload = $this->decodeToken($token);
        return $payload['sub'] ?? null;
    }

    /**
     * Get user email from token
     */
    public function getEmailFromToken($token)
    {
        $payload = $this->decodeToken($token);
        return $payload['email'] ?? null;
    }

    /**
     * Get user information from token
     */
    public function getUserFromToken($token)
    {
        $payload = $this->decodeToken($token);
        return [
            'id' => $payload['sub'] ?? null,
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? null
        ];
    }

    /**
     * Refresh an access token using a refresh token
     */
    public function refreshToken($refreshToken)
    {
        $payload = $this->decodeToken($refreshToken);
        
        if ($payload['type'] !== 'refresh') {
            throw new JWTException('Invalid refresh token type');
        }

        if (time() > $payload['exp']) {
            throw new JWTException('Refresh token expired');
        }

        // Generate new token pair
        return $this->generateTokenPair($payload['sub'], $payload['email']);
    }

    /**
     * Generate both access and refresh tokens
     */
    public function generateTokenPair($userId, $email, $additionalClaims = [])
    {
        return [
            'access_token' => $this->generateToken($userId, $email, $additionalClaims),
            'refresh_token' => $this->generateRefreshToken($userId, $email),
            'token_type' => 'Bearer',
            'expires_in' => $this->expiration
        ];
    }

    /**
     * Extract token from Authorization header
     */
    public function extractTokenFromHeader($authHeader)
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7); // Remove 'Bearer ' prefix
    }

    /**
     * Blacklist a token (simple implementation - in production use Redis/Database)
     */
    private $blacklistedTokens = [];

    public function blacklistToken($token)
    {
        try {
            $payload = $this->decodeToken($token);
            $this->blacklistedTokens[$payload['iat']] = true;
        } catch (Exception $e) {
            // Token is invalid, nothing to blacklist
        }
    }

    public function isTokenBlacklisted($token)
    {
        try {
            $payload = $this->decodeToken($token);
            return isset($this->blacklistedTokens[$payload['iat']]);
        } catch (Exception $e) {
            // Token is invalid, consider it not blacklisted
            return false;
        }
    }
}