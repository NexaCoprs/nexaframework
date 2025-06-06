<?php

namespace Nexa\Core;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Répertoire des logs
     *
     * @var string
     */
    protected static $logPath;

    /**
     * Niveau de log minimum
     *
     * @var string
     */
    protected static $minLevel = self::DEBUG;

    /**
     * Format de date
     *
     * @var string
     */
    protected static $dateFormat = 'Y-m-d H:i:s';

    /**
     * Niveaux de log avec leurs priorités
     *
     * @var array
     */
    protected static $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7,
    ];

    /**
     * Initialise le logger
     *
     * @param string $logPath
     * @param string $minLevel
     * @return void
     */
    public static function init(string $logPath, string $minLevel = self::DEBUG)
    {
        static::$logPath = rtrim($logPath, '/\\');
        static::$minLevel = $minLevel;
        
        // Créer le répertoire s'il n'existe pas
        if (!is_dir(static::$logPath)) {
            mkdir(static::$logPath, 0755, true);
        }
    }

    /**
     * Log un message d'urgence
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function emergency(string $message, array $context = [])
    {
        static::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log un message d'alerte
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function alert(string $message, array $context = [])
    {
        static::log(self::ALERT, $message, $context);
    }

    /**
     * Log un message critique
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical(string $message, array $context = [])
    {
        static::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log un message d'erreur
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error(string $message, array $context = [])
    {
        static::log(self::ERROR, $message, $context);
    }

    /**
     * Log un message d'avertissement
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning(string $message, array $context = [])
    {
        static::log(self::WARNING, $message, $context);
    }

    /**
     * Log un message de notice
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function notice(string $message, array $context = [])
    {
        static::log(self::NOTICE, $message, $context);
    }

    /**
     * Log un message d'information
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info(string $message, array $context = [])
    {
        static::log(self::INFO, $message, $context);
    }

    /**
     * Log un message de débogage
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug(string $message, array $context = [])
    {
        static::log(self::DEBUG, $message, $context);
    }

    /**
     * Log un message avec un niveau spécifique
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log(string $level, string $message, array $context = [])
    {
        // Vérifier si le niveau est suffisant
        if (!static::shouldLog($level)) {
            return;
        }

        $timestamp = date(static::$dateFormat);
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextString}" . PHP_EOL;

        // Déterminer le fichier de log
        $filename = static::getLogFilename($level);
        $filepath = static::$logPath . '/' . $filename;

        // Écrire dans le fichier
        file_put_contents($filepath, $logEntry, FILE_APPEND | LOCK_EX);

        // Log également dans le fichier général
        if ($filename !== 'app.log') {
            $generalPath = static::$logPath . '/app.log';
            file_put_contents($generalPath, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Vérifie si un niveau doit être loggé
     *
     * @param string $level
     * @return bool
     */
    protected static function shouldLog(string $level)
    {
        if (!isset(static::$levels[$level]) || !isset(static::$levels[static::$minLevel])) {
            return false;
        }

        return static::$levels[$level] <= static::$levels[static::$minLevel];
    }

    /**
     * Obtient le nom du fichier de log pour un niveau
     *
     * @param string $level
     * @return string
     */
    protected static function getLogFilename(string $level)
    {
        switch ($level) {
            case self::ERROR:
            case self::CRITICAL:
            case self::EMERGENCY:
            case self::ALERT:
                return 'error.log';
            case self::WARNING:
                return 'warning.log';
            case self::DEBUG:
                return 'debug.log';
            default:
                return 'app.log';
        }
    }

    /**
     * Log une exception
     *
     * @param \Throwable $exception
     * @param string $level
     * @return void
     */
    public static function exception(\Throwable $exception, string $level = self::ERROR)
    {
        $message = sprintf(
            'Exception: %s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        static::log($level, $message, $context);
    }

    /**
     * Log une requête HTTP
     *
     * @param string $method
     * @param string $uri
     * @param int $statusCode
     * @param float $responseTime
     * @return void
     */
    public static function request(string $method, string $uri, int $statusCode, float $responseTime = null)
    {
        $message = sprintf('%s %s - %d', $method, $uri, $statusCode);
        
        $context = [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        if ($responseTime !== null) {
            $context['response_time'] = round($responseTime * 1000, 2) . 'ms';
        }

        static::info($message, $context);
    }

    /**
     * Log une requête de base de données
     *
     * @param string $query
     * @param array $bindings
     * @param float $time
     * @return void
     */
    public static function query(string $query, array $bindings = [], float $time = null)
    {
        $message = 'Database Query: ' . $query;
        
        $context = [
            'query' => $query,
            'bindings' => $bindings
        ];

        if ($time !== null) {
            $context['execution_time'] = round($time * 1000, 2) . 'ms';
        }

        static::debug($message, $context);
    }

    /**
     * Nettoie les anciens fichiers de log
     *
     * @param int $days
     * @return void
     */
    public static function cleanup(int $days = 30)
    {
        if (!is_dir(static::$logPath)) {
            return;
        }

        $files = glob(static::$logPath . '/*.log');
        $cutoff = time() - ($days * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }

    /**
     * Définit le niveau de log minimum
     *
     * @param string $level
     * @return void
     */
    public static function setMinLevel(string $level)
    {
        static::$minLevel = $level;
    }

    /**
     * Obtient le niveau de log minimum
     *
     * @return string
     */
    public static function getMinLevel()
    {
        return static::$minLevel;
    }
}