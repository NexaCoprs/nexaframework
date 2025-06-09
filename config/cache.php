<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [
        'file' => [
            'driver' => 'file',
            'path' => storage_path('cache'),
            'prefix' => env('CACHE_PREFIX', 'nexa_'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => env('CACHE_PREFIX', 'nexa_'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
            'prefix' => env('CACHE_PREFIX', 'nexa_'),
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'prefix' => env('CACHE_PREFIX', 'nexa_'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', 'nexa_'),

    /*
    |--------------------------------------------------------------------------
    | Default Cache TTL
    |--------------------------------------------------------------------------
    |
    | This option controls the default time-to-live (TTL) for cache items
    | when no specific TTL is provided. The value is in seconds.
    |
    */

    'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // 1 hour

    /*
    |--------------------------------------------------------------------------
    | Cache Cleanup
    |--------------------------------------------------------------------------
    |
    | This option controls automatic cleanup of expired cache files.
    | Set cleanup_enabled to true to enable automatic cleanup.
    |
    */

    'cleanup' => [
        'enabled' => env('CACHE_CLEANUP_ENABLED', true),
        'probability' => env('CACHE_CLEANUP_PROBABILITY', 2), // 2% chance on each request
        'max_files_per_cleanup' => env('CACHE_CLEANUP_MAX_FILES', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Enable cache tagging for better cache invalidation strategies.
    | Note: Not all cache drivers support tagging.
    |
    */

    'tags' => [
        'enabled' => env('CACHE_TAGS_ENABLED', false),
        'separator' => env('CACHE_TAGS_SEPARATOR', ':'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Serialization
    |--------------------------------------------------------------------------
    |
    | Configure how cache values should be serialized and unserialized.
    |
    */

    'serialization' => [
        'method' => env('CACHE_SERIALIZATION', 'serialize'), // serialize, json, igbinary
        'compress' => env('CACHE_COMPRESS', false),
    ],
];