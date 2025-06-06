<?php

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/storage' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the app folder.
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install.
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the config folder.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/config' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/public' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        $basePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 3);
        return $basePath . '/resources' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }
        
        if (strlen($value) > 1 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return substr($value, 1, -1);
        }
        
        return $value;
    }
}