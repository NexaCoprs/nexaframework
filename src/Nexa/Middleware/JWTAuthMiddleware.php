<?php

namespace Nexa\Middleware;

use Closure;
use Nexa\Auth\JWTManager;
use Nexa\Auth\JWTException;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Http\Middleware\MiddlewareInterface;

class JWTAuthMiddleware implements MiddlewareInterface
{
    private $jwtManager;
    private $excludedRoutes;

    public function __construct(?JWTManager $jwtManager = null, array $excludedRoutes = [])
    {
        $this->jwtManager = $jwtManager ?: new JWTManager();
        $this->excludedRoutes = $excludedRoutes;
    }

    public function handle($request, Closure $next)
    {
        // Check if current route should be excluded from authentication
        if ($this->shouldExcludeRoute($request)) {
            return $next($request);
        }

        try {
            // Get authorization header
            $authHeader = $request->getHeader('Authorization');
            
            if (!$authHeader) {
                throw JWTException::missing();
            }

            // Extract token from header
            $token = $this->jwtManager->extractTokenFromHeader($authHeader);
            
            // Validate token
            $payload = $this->jwtManager->validateToken($token);
            
            // Check if token is blacklisted
            if ($this->jwtManager->isTokenBlacklisted($token)) {
                throw JWTException::blacklisted();
            }

            // Check if token is expired
            if ($this->jwtManager->isTokenExpired($token)) {
                throw JWTException::expired();
            }

            // Add user information to request
            $request->setUser([
                'id' => $payload['sub'],
                'email' => $payload['email'],
                'token' => $token,
                'payload' => $payload
            ]);

            return $next($request);

        } catch (JWTException $e) {
            return $this->unauthorizedResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Authentication failed', 401);
        }
    }

    /**
     * Check if the current route should be excluded from authentication
     */
    private function shouldExcludeRoute(Request $request)
    {
        $currentPath = $request->getPath();
        $currentMethod = $request->getMethod();

        foreach ($this->excludedRoutes as $route) {
            if (is_string($route)) {
                // Simple path matching
                if ($this->matchPath($currentPath, $route)) {
                    return true;
                }
            } elseif (is_array($route)) {
                // Method and path matching
                $routePath = $route['path'] ?? '';
                $routeMethods = $route['methods'] ?? ['GET', 'POST', 'PUT', 'DELETE'];
                
                if (in_array($currentMethod, $routeMethods) && $this->matchPath($currentPath, $routePath)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Match path with wildcards support
     */
    private function matchPath($currentPath, $routePath)
    {
        // Convert route pattern to regex
        $pattern = str_replace(['*', '/'], ['.*', '\/'], $routePath);
        $pattern = '/^' . $pattern . '$/i';
        
        return preg_match($pattern, $currentPath);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse($message, $code = 401)
    {
        $response = new Response();
        $response->setStatusCode($code);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ]));
        
        return $response;
    }

    /**
     * Set excluded routes
     */
    public function setExcludedRoutes(array $routes)
    {
        $this->excludedRoutes = $routes;
        return $this;
    }

    /**
     * Add excluded route
     */
    public function addExcludedRoute($route)
    {
        $this->excludedRoutes[] = $route;
        return $this;
    }
}