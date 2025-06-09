<?php

namespace Nexa\Core;

class Config
{
    /**
     * Tableau des configurations chargées
     *
     * @var array
     */
    protected static $config = [];

    /**
     * Répertoire des fichiers de configuration
     *
     * @var string
     */
    protected static $configPath;

    /**
     * Initialise la configuration
     *
     * @param string $configPath
     * @return void
     */
    public static function init(string $configPath)
    {
        static::$configPath = rtrim($configPath, '/\\');
        static::loadAllConfigs();
    }

    /**
     * Charge tous les fichiers de configuration
     *
     * @return void
     */
    protected static function loadAllConfigs()
    {
        if (!is_dir(static::$configPath)) {
            return;
        }

        $files = glob(static::$configPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            static::$config[$key] = require $file;
        }
    }

    /**
     * Obtient une valeur de configuration
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = static::$config;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Définit une valeur de configuration
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, $value)
    {
        $keys = explode('.', $key);
        $config = &static::$config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Vérifie si une clé de configuration existe
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key)
    {
        $keys = explode('.', $key);
        $value = static::$config;

        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtient toute la configuration
     *
     * @return array
     */
    public static function all()
    {
        return static::$config;
    }

    /**
     * Obtient une configuration par fichier
     *
     * @param string $file
     * @return array
     */
    public static function getFile(string $file)
    {
        return static::$config[$file] ?? [];
    }

    /**
     * Recharge un fichier de configuration
     *
     * @param string $file
     * @return void
     */
    public static function reload(string $file)
    {
        $filePath = static::$configPath . '/' . $file . '.php';
        
        if (file_exists($filePath)) {
            static::$config[$file] = require $filePath;
        }
    }

    /**
     * Recharge toutes les configurations
     *
     * @return void
     */
    public static function reloadAll()
    {
        static::$config = [];
        static::loadAllConfigs();
    }

    /**
     * Obtient une variable d'environnement avec une valeur par défaut
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? getenv($key);
        
        if ($value === false) {
            return $default;
        }

        // Convertir les valeurs booléennes
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

        // Supprimer les guillemets
        if (strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Fusionne des configurations
     *
     * @param array $config
     * @return void
     */
    public static function merge(array $config)
    {
        static::$config = array_merge_recursive(static::$config, $config);
    }

    /**
     * Efface toute la configuration
     *
     * @return void
     */
    public static function clear()
    {
        static::$config = [];
    }
}