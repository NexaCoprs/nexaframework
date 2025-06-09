<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Nexa uses the Monolog PHP logging library. This gives you
    | a variety of powerful log handlers / formatters to utilize.
    |
    */

    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/nexa.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/nexa.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Nexa Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'driver' => 'single',
            'path' => storage_path('logs/emergency.log'),
            'level' => 'emergency',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Cleanup
    |--------------------------------------------------------------------------
    |
    | This option controls how long log files should be kept before being
    | automatically deleted. Set to 0 to disable automatic cleanup.
    |
    */

    'cleanup_days' => env('LOG_CLEANUP_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Log Performance
    |--------------------------------------------------------------------------
    |
    | Enable or disable performance logging for database queries, requests,
    | and other operations.
    |
    */

    'performance' => [
        'enabled' => env('LOG_PERFORMANCE', false),
        'slow_query_threshold' => env('LOG_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'slow_request_threshold' => env('LOG_SLOW_REQUEST_THRESHOLD', 5000), // milliseconds
    ],
];