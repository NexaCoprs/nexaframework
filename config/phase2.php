<?php

/**
 * Phase 2 Configuration
 * 
 * Configuration for advanced features introduced in Phase 2:
 * - JWT Authentication
 * - Event System
 * - Queue System
 * - Testing Framework
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | JWT Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JSON Web Token authentication system.
    |
    */
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-here',
        'algorithm' => 'HS256',
        'access_token_ttl' => 3600, // 1 hour in seconds
        'refresh_token_ttl' => 604800, // 7 days in seconds
        'issuer' => $_ENV['JWT_ISSUER'] ?? 'nexa-framework',
        'audience' => $_ENV['JWT_AUDIENCE'] ?? 'nexa-app',
        'blacklist_enabled' => true,
        'blacklist_grace_period' => 300, // 5 minutes
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Event System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the event dispatcher and listener system.
    |
    */
    'events' => [
        'enable_logging' => $_ENV['EVENT_LOGGING'] ?? true,
        'log_channel' => 'events',
        'auto_discover_listeners' => true,
        'listener_directories' => [
            'app/Listeners',
        ],
        'max_listeners_per_event' => 50,
        'async_events' => [
            // Events that should be processed asynchronously
            'UserRegistered',
            'ModelCreated',
            'ModelUpdated',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Queue System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the job queue system.
    |
    */
    'queue' => [
        'default' => $_ENV['QUEUE_DRIVER'] ?? 'sync',
        
        'drivers' => [
            'sync' => [
                'driver' => 'sync',
            ],
            
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'failed_table' => 'failed_jobs',
                'retry_after' => 90, // seconds
                'max_attempts' => 3,
            ],
        ],
        
        'queues' => [
            'default' => [
                'timeout' => 60,
                'memory' => 128, // MB
                'sleep' => 3, // seconds between job checks
            ],
            'emails' => [
                'timeout' => 30,
                'memory' => 64,
                'sleep' => 1,
            ],
            'images' => [
                'timeout' => 300, // 5 minutes for image processing
                'memory' => 256,
                'sleep' => 5,
            ],
        ],
        
        'failed_jobs' => [
            'retain_for' => 7, // days
            'auto_retry' => false,
            'max_retries' => 3,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the testing framework.
    |
    */
    'testing' => [
        'test_directories' => [
            'tests',
        ],
        'test_namespace' => 'Tests',
        'test_database' => [
            'driver' => $_ENV['TEST_DB_DRIVER'] ?? 'sqlite',
            'host' => $_ENV['TEST_DB_HOST'] ?? ':memory:',
            'database' => $_ENV['TEST_DB_NAME'] ?? ':memory:',
            'username' => $_ENV['TEST_DB_USER'] ?? '',
            'password' => $_ENV['TEST_DB_PASSWORD'] ?? '',
        ],
        'coverage' => [
            'enabled' => false,
            'output_directory' => 'storage/coverage',
            'include_paths' => [
                'src/',
                'app/',
            ],
            'exclude_paths' => [
                'vendor/',
                'tests/',
                'storage/',
            ],
        ],
        'reports' => [
            'formats' => ['json', 'xml', 'html'],
            'output_directory' => 'storage/test-reports',
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | CLI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the command-line interface.
    |
    */
    'cli' => [
        'commands' => [
            // Custom command classes can be registered here
        ],
        'auto_discover_commands' => true,
        'command_directories' => [
            'app/Console/Commands',
        ],
        'output' => [
            'colors' => true,
            'verbosity' => 'normal', // quiet, normal, verbose, debug
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Additional security settings for Phase 2 features.
    |
    */
    'security' => [
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60, // per minute
            'decay_minutes' => 1,
        ],
        'cors' => [
            'enabled' => true,
            'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'max_age' => 86400, // 24 hours
            'supports_credentials' => false,
        ],
        'csrf' => [
            'enabled' => true,
            'token_lifetime' => 3600, // 1 hour
            'regenerate_on_login' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Performance-related settings for Phase 2 features.
    |
    */
    'performance' => [
        'caching' => [
            'events' => true,
            'routes' => true,
            'config' => true,
            'views' => true,
        ],
        'optimization' => [
            'eager_load_listeners' => false,
            'compile_routes' => false,
            'minify_responses' => false,
        ],
        'monitoring' => [
            'enabled' => $_ENV['MONITORING_ENABLED'] ?? false,
            'slow_query_threshold' => 1000, // milliseconds
            'memory_usage_alerts' => true,
            'performance_logging' => true,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Development Configuration
    |--------------------------------------------------------------------------
    |
    | Settings specific to development environment.
    |
    */
    'development' => [
        'debug_mode' => $_ENV['APP_DEBUG'] ?? false,
        'profiling' => [
            'enabled' => false,
            'include_database' => true,
            'include_events' => true,
            'include_queue' => true,
        ],
        'hot_reload' => [
            'enabled' => false,
            'watch_directories' => [
                'src/',
                'app/',
                'config/',
                'resources/views/',
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for third-party integrations.
    |
    */
    'integrations' => [
        'email' => [
            'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
            'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from' => [
                'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com',
                'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Nexa Framework',
            ],
        ],
        'storage' => [
            'default' => $_ENV['STORAGE_DRIVER'] ?? 'local',
            'drivers' => [
                'local' => [
                    'root' => 'storage/app',
                ],
                's3' => [
                    'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? '',
                    'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '',
                    'region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
                    'bucket' => $_ENV['AWS_BUCKET'] ?? '',
                ],
            ],
        ],
    ],
];