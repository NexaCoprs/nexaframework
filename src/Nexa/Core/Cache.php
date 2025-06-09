<?php

namespace Nexa\Core;

class Cache
{
    /**
     * Répertoire de cache
     *
     * @var string
     */
    protected static $cachePath;

    /**
     * Préfixe pour les clés de cache
     *
     * @var string
     */
    protected static $prefix = 'nexa_';

    /**
     * TTL par défaut en secondes
     *
     * @var int
     */
    protected static $defaultTtl = 3600; // 1 heure

    /**
     * Initialise le cache
     *
     * @param string $cachePath
     * @param string $prefix
     * @param int $defaultTtl
     * @return void
     */
    public static function init(string $cachePath, string $prefix = 'nexa_', int $defaultTtl = 3600)
    {
        static::$cachePath = rtrim($cachePath, '/\\');
        static::$prefix = $prefix;
        static::$defaultTtl = $defaultTtl;
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir(static::$cachePath)) {
            mkdir(static::$cachePath, 0755, true);
        }
    }

    /**
     * Stocke une valeur dans le cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public static function put(string $key, $value, int $ttl = null)
    {
        $ttl = $ttl ?? static::$defaultTtl;
        $filename = static::getFilename($key);
        $filepath = static::$cachePath . '/' . $filename;
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        $serialized = serialize($data);
        
        return file_put_contents($filepath, $serialized, LOCK_EX) !== false;
    }

    /**
     * Récupère une valeur du cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $filename = static::getFilename($key);
        $filepath = static::$cachePath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return $default;
        }
        
        $content = file_get_contents($filepath);
        if ($content === false) {
            return $default;
        }
        
        $data = unserialize($content);
        if ($data === false) {
            // Fichier corrompu, le supprimer
            unlink($filepath);
            return $default;
        }
        
        // Vérifier l'expiration
        if (time() > $data['expires_at']) {
            unlink($filepath);
            return $default;
        }
        
        return $data['value'];
    }

    /**
     * Vérifie si une clé existe dans le cache
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key)
    {
        $filename = static::getFilename($key);
        $filepath = static::$cachePath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        $content = file_get_contents($filepath);
        if ($content === false) {
            return false;
        }
        
        $data = unserialize($content);
        if ($data === false) {
            unlink($filepath);
            return false;
        }
        
        // Vérifier l'expiration
        if (time() > $data['expires_at']) {
            unlink($filepath);
            return false;
        }
        
        return true;
    }

    /**
     * Supprime une clé du cache
     *
     * @param string $key
     * @return bool
     */
    public static function forget(string $key)
    {
        $filename = static::getFilename($key);
        $filepath = static::$cachePath . '/' . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return true;
    }

    /**
     * Vide tout le cache
     *
     * @return bool
     */
    public static function flush()
    {
        if (!is_dir(static::$cachePath)) {
            return true;
        }
        
        $files = glob(static::$cachePath . '/' . static::$prefix . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * Récupère ou stocke une valeur
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = static::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        static::put($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Récupère ou stocke une valeur pour toujours
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public static function rememberForever(string $key, callable $callback)
    {
        return static::remember($key, $callback, 365 * 24 * 3600); // 1 an
    }

    /**
     * Stocke une valeur pour toujours
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function forever(string $key, $value)
    {
        return static::put($key, $value, 365 * 24 * 3600); // 1 an
    }

    /**
     * Incrémente une valeur numérique
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public static function increment(string $key, int $value = 1)
    {
        $current = static::get($key, 0);
        
        if (!is_numeric($current)) {
            return false;
        }
        
        $new = $current + $value;
        static::put($key, $new);
        
        return $new;
    }

    /**
     * Décrémente une valeur numérique
     *
     * @param string $key
     * @param int $value
     * @return int|false
     */
    public static function decrement(string $key, int $value = 1)
    {
        return static::increment($key, -$value);
    }

    /**
     * Ajoute une valeur seulement si la clé n'existe pas
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public static function add(string $key, $value, int $ttl = null)
    {
        if (static::has($key)) {
            return false;
        }
        
        return static::put($key, $value, $ttl);
    }

    /**
     * Obtient plusieurs valeurs du cache
     *
     * @param array $keys
     * @return array
     */
    public static function many(array $keys)
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = static::get($key);
        }
        
        return $result;
    }

    /**
     * Stocke plusieurs valeurs dans le cache
     *
     * @param array $values
     * @param int|null $ttl
     * @return bool
     */
    public static function putMany(array $values, int $ttl = null)
    {
        $success = true;
        
        foreach ($values as $key => $value) {
            if (!static::put($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Nettoie les fichiers de cache expirés
     *
     * @return int Nombre de fichiers supprimés
     */
    public static function cleanup()
    {
        if (!is_dir(static::$cachePath)) {
            return 0;
        }
        
        $files = glob(static::$cachePath . '/' . static::$prefix . '*');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }
            
            $data = unserialize($content);
            if ($data === false || time() > $data['expires_at']) {
                unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }

    /**
     * Obtient des statistiques sur le cache
     *
     * @return array
     */
    public static function stats()
    {
        if (!is_dir(static::$cachePath)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'expired_files' => 0
            ];
        }
        
        $files = glob(static::$cachePath . '/' . static::$prefix . '*');
        $totalFiles = 0;
        $totalSize = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $totalFiles++;
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $data = unserialize($content);
                if ($data !== false && time() > $data['expires_at']) {
                    $expiredFiles++;
                }
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'expired_files' => $expiredFiles,
            'cache_path' => static::$cachePath
        ];
    }

    /**
     * Génère un nom de fichier pour une clé
     *
     * @param string $key
     * @return string
     */
    protected static function getFilename(string $key)
    {
        return static::$prefix . md5($key) . '.cache';
    }
}