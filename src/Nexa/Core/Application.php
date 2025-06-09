<?php

namespace Nexa\Core;

use Nexa\Core\Config;
use Nexa\Core\Logger;
use Nexa\Core\Cache;

class Application
{
    protected $basePath;
    protected $config = [];
    protected $bindings = [];
    protected $instances = [];
    protected $serviceProviders = [];

    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
            // Set the base path in environment for helper functions
            $_ENV['APP_BASE_PATH'] = $this->basePath;
        }

        $this->bootstrap();
    }

    protected function bootstrap()
    {
        // Initialize core services
        $this->initializeConfig();
        $this->initializeLogger();
        $this->initializeCache();
        
        // Register core services
        $this->registerErrorHandler();
        $this->loadConfiguration();
        $this->registerServiceProviders();
    }

    protected function registerErrorHandler()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    protected function initializeConfig()
    {
        $configPath = $this->basePath . '/config';
        Config::init($configPath);
    }
    
    protected function initializeLogger()
    {
        $logPath = $this->basePath . '/storage/logs';
        $minLevel = Config::env('LOG_LEVEL', Logger::INFO);
        Logger::init($logPath, $minLevel);
    }
    
    protected function initializeCache()
    {
        $cachePath = $this->basePath . '/storage/cache';
        $prefix = Config::env('CACHE_PREFIX', 'nexa_');
        $defaultTtl = (int) Config::env('CACHE_DEFAULT_TTL', 3600);
        Cache::init($cachePath, $prefix, $defaultTtl);
    }

    protected function loadConfiguration()
    {
        // Configuration is now handled by Config class
        $this->config = Config::all();
    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        return $this;
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        $errorMessage = "Error [{$level}]: {$message} in {$file}:{$line}";
        Logger::error($errorMessage, $context);
        
        // En mode debug, afficher l'erreur
        if (Config::get('app.debug', false)) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<strong>Error:</strong> {$errorMessage}";
            echo "</div>";
        }
    }

    public function handleException($exception)
    {
        Logger::exception($exception);
        
        // En mode debug, afficher l'exception complète
        if (Config::get('app.debug', false)) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 4px;'>";
            echo "<h3>Exception: " . get_class($exception) . "</h3>";
            echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $exception->getFile() . ":" . $exception->getLine() . "</p>";
            echo "<details><summary>Stack Trace</summary><pre>" . $exception->getTraceAsString() . "</pre></details>";
            echo "</div>";
        } else {
            // En production, afficher une page d'erreur générique
            http_response_code(500);
            echo "<h1>500 - Internal Server Error</h1><p>Something went wrong. Please try again later.</p>";
        }
    }

    protected function registerServiceProviders()
    {
        $providers = $this->config['app']['providers'] ?? [];
        
        foreach ($providers as $providerClass) {
            $provider = new $providerClass($this);
            $provider->register();
            $this->serviceProviders[] = $provider;
        }
        
        // Boot all providers
        foreach ($this->serviceProviders as $provider) {
            $provider->boot();
        }
    }

    public function bind($abstract, $concrete = null)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    public function make($abstract)
    {
        if (isset($this->instances[$abstract]) && $this->instances[$abstract] !== null) {
            return $this->instances[$abstract];
        }
        
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            
            if (is_callable($concrete)) {
                $instance = $concrete();
            } else {
                $instance = new $concrete();
            }
            
            if (array_key_exists($abstract, $this->instances)) {
                $this->instances[$abstract] = $instance;
            }
            
            return $instance;
        }
        
        return new $abstract();
    }

    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function run()
    {
        try {
            // Get the router instance
            $router = $this->make('router');
            
            // Get current request URI and method
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $method = $_SERVER['REQUEST_METHOD'];
            
            // Log the request for debugging
            Logger::info("Request: {$method} {$uri}");
            
            // Dispatch the request
            $response = $router->dispatch($method, $uri);
            
            // Send the response
            if (is_string($response)) {
                echo $response;
            } elseif (is_array($response) || is_object($response)) {
                header('Content-Type: application/json');
                echo json_encode($response);
            }
        } catch (\Exception $e) {
            // Log the error
            Logger::error('Application error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ]);
            
            // Handle exceptions
            if ($this->config['app']['debug'] ?? false) {
                echo '<h1>Error</h1>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            } else {
                // In production, show a generic error page
                http_response_code(500);
                echo '<h1>500 - Internal Server Error</h1>';
                echo '<p>Une erreur est survenue. Veuillez réessayer plus tard.</p>';
            }
        }
    }
}