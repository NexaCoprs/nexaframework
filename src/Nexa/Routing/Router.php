<?php

namespace Nexa\Routing;

class Router
{
    protected $routes = [];
    protected $currentGroup = [];

    public function get($uri, $action)
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch($uri, $action)
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function group(array $attributes, \Closure $callback)
    {
        $this->currentGroup[] = $attributes;

        $callback($this);

        array_pop($this->currentGroup);
    }

    protected function addRoute($method, $uri, $action)
    {
        $route = $this->createRoute($method, $uri, $action);

        $this->routes[$method][] = $route;

        return $route;
    }

    protected function createRoute($method, $uri, $action)
    {
        $uri = $this->prefixUri($uri);

        return new Route($method, $uri, $action);
    }

    protected function prefixUri($uri)
    {
        if (empty($this->currentGroup)) {
            return $uri;
        }

        $prefix = '';

        foreach ($this->currentGroup as $group) {
            if (isset($group['prefix'])) {
                $prefix = trim($group['prefix'], '/') . '/' . trim($prefix, '/');
            }
        }

        $prefix = trim($prefix, '/');
        $uri = trim($uri, '/');
        
        if (empty($prefix)) {
            return $uri ?: '/';
        }
        
        if (empty($uri)) {
            return '/' . $prefix;  // Ajout du slash initial
        }
        
        return '/' . $prefix . '/' . $uri;  // Ajout du slash initial
    }

    public function dispatch($method, $uri)
    {
        if (!isset($this->routes[$method])) {
            throw new \Exception("Route not found for method: $method");
        }

        foreach ($this->routes[$method] as $route) {
            if ($route->matches($uri)) {
                return $route->run();
            }
        }

        throw new \Exception("Route not found for URI: $uri");
    }

    /**
     * Merge routes from another router instance
     *
     * @param Router $router
     * @return void
     */
    public function mergeRouters(Router $router)
    {
        $otherRoutes = $router->getRoutes();
        
        foreach ($otherRoutes as $method => $routes) {
            if (!isset($this->routes[$method])) {
                $this->routes[$method] = [];
            }
            
            $this->routes[$method] = array_merge($this->routes[$method], $routes);
        }
    }

    /**
     * Get all routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}