<?php

namespace Nexa\Routing;

use Nexa\Http\Request;

class Route
{
    protected $method;
    protected $uri;
    protected $action;
    protected $parameters = [];

    public function __construct($method, $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function matches($uri)
    {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $this->uri);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            $this->parameters = $matches;
            return true;
        }
        
        return false;
    }

    public function run()
    {
        if (is_string($this->action)) {
            return $this->runController($this->action);
        }
        
        if (is_array($this->action) && count($this->action) === 2) {
            list($class, $method) = $this->action;
            return $this->runControllerArray($class, $method);
        }
        
        if (is_callable($this->action)) {
            return call_user_func($this->action, ...$this->parameters);
        }
        
        throw new \Exception("Invalid route action");
    }

    protected function runController($controller)
    {
        list($class, $method) = explode('@', $controller);
        
        if (!class_exists($class)) {
            throw new \Exception("Controller {$class} not found");
        }
        
        $instance = new $class;
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Method {$method} not found in controller {$class}");
        }
        
        return $instance->$method(...$this->parameters);
    }
    
    protected function runControllerArray($class, $method)
    {
        if (!class_exists($class)) {
            throw new \Exception("Controller {$class} not found");
        }
        
        $instance = new $class;
        
        if (!method_exists($instance, $method)) {
            throw new \Exception("Method {$method} not found in controller {$class}");
        }
        
        // Create Request instance and inject it as the first parameter
        $request = Request::capture();
        $parameters = array_merge([$request], $this->parameters);
        
        return $instance->$method(...$parameters);
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getMethod()
    {
        return $this->method;
    }
}