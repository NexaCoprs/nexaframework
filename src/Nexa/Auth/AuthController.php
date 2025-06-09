<?php

namespace Nexa\Auth;

use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Database\Model;
use Nexa\Validation\Validator;
use Nexa\Auth\JWTManager;
use Nexa\Auth\JWTException;

class AuthController
{
    private $jwtManager;
    private $userModel;

    public function __construct(JWTManager $jwtManager = null, $userModel = null)
    {
        $this->jwtManager = $jwtManager ?: new JWTManager();
        $this->userModel = $userModel ?: 'App\Models\User';
    }

    /**
     * User registration
     */
    public function register(Request $request)
    {
        try {
            // Validate input
            $validator = new Validator($request->input(), [
                'name' => 'required|string|min:2|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Create user
            $userClass = $this->userModel;
            $user = new $userClass();
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
            $user->email_verified_at = null;
            $user->created_at = date('Y-m-d H:i:s');
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            // Generate tokens
            $tokens = $this->jwtManager->generateTokenPair($user->id, $user->email, [
                'name' => $user->name
            ]);

            return $this->successResponse('User registered successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at
                ],
                'tokens' => $tokens
            ], 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * User login
     */
    public function login(Request $request)
    {
        try {
            // Validate input
            $validator = new Validator($request->input(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Find user
            $userClass = $this->userModel;
            $user = $userClass::where('email', $request->input('email'))->first();

            if (!$user || !password_verify($request->input('password'), $user->password)) {
                return $this->errorResponse('Invalid credentials', [], 401);
            }

            // Generate tokens
            $tokens = $this->jwtManager->generateTokenPair($user->id, $user->email, [
                'name' => $user->name
            ]);

            // Update last login
            $user->last_login_at = date('Y-m-d H:i:s');
            $user->save();

            return $this->successResponse('Login successful', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at
                ],
                'tokens' => $tokens
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Login failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Refresh access token
     */
    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->input('refresh_token');
            
            if (!$refreshToken) {
                return $this->errorResponse('Refresh token required', [], 400);
            }

            // Generate new access token
            $newAccessToken = $this->jwtManager->refreshToken($refreshToken);
            
            return $this->successResponse('Token refreshed successfully', [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ]);

        } catch (JWTException $e) {
            return $this->errorResponse($e->getMessage(), [], $e->getCode());
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $token = $request->userToken();
            
            if ($token) {
                // Blacklist the token
                $this->jwtManager->blacklistToken($token);
            }

            return $this->successResponse('Logout successful', []);

        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        try {
            $userId = $request->userId();
            
            if (!$userId) {
                return $this->errorResponse('User not authenticated', [], 401);
            }

            // Get user details
            $userClass = $this->userModel;
            $user = $userClass::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            return $this->successResponse('Profile retrieved successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at ?? null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        try {
            $userId = $request->userId();
            
            if (!$userId) {
                return $this->errorResponse('User not authenticated', [], 401);
            }

            // Validate input
            $validator = new Validator($request->input(), [
                'name' => 'sometimes|required|string|min:2|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $userId
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Get and update user
            $userClass = $this->userModel;
            $user = $userClass::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            if ($request->has('name')) {
                $user->name = $request->input('name');
            }

            if ($request->has('email')) {
                $user->email = $request->input('email');
                $user->email_verified_at = null; // Reset email verification
            }

            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            return $this->successResponse('Profile updated successfully', [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $userId = $request->userId();
            
            if (!$userId) {
                return $this->errorResponse('User not authenticated', [], 401);
            }

            // Validate input
            $validator = new Validator($request->input(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Get user
            $userClass = $this->userModel;
            $user = $userClass::find($userId);

            if (!$user) {
                return $this->errorResponse('User not found', [], 404);
            }

            // Verify current password
            if (!password_verify($request->input('current_password'), $user->password)) {
                return $this->errorResponse('Current password is incorrect', [], 400);
            }

            // Update password
            $user->password = password_hash($request->input('new_password'), PASSWORD_DEFAULT);
            $user->updated_at = date('Y-m-d H:i:s');
            $user->save();

            return $this->successResponse('Password changed successfully', []);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change password', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Return success response
     */
    private function successResponse($message, $data = [], $statusCode = 200)
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]));
        
        return $response;
    }

    /**
     * Return error response
     */
    private function errorResponse($message, $errors = [], $statusCode = 400)
    {
        $response = new Response();
        $response->setStatusCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ]));
        
        return $response;
    }
}