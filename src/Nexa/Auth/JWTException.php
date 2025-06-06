<?php

namespace Nexa\Auth;

use Exception;

class JWTException extends Exception
{
    public function __construct($message = 'JWT Authentication Error', $code = 401, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for expired token
     */
    public static function expired()
    {
        return new static('Token has expired', 401);
    }

    /**
     * Create exception for invalid token
     */
    public static function invalid()
    {
        return new static('Invalid token', 401);
    }

    /**
     * Create exception for missing token
     */
    public static function missing()
    {
        return new static('No token provided', 401);
    }

    /**
     * Create exception for blacklisted token
     */
    public static function blacklisted()
    {
        return new static('Token has been blacklisted', 401);
    }

    /**
     * Create exception for insufficient permissions
     */
    public static function unauthorized()
    {
        return new static('Insufficient permissions', 403);
    }
}