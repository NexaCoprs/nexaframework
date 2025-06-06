<?php

return [
    'name' => 'Nexa Framework',
    'env' => 'production',
    'debug' => false,
    'providers' => [
        Nexa\Database\DatabaseServiceProvider::class,
        Nexa\View\ViewServiceProvider::class,
        Nexa\Routing\RoutingServiceProvider::class,
    ],
    'log_level' => 'error', // Only log errors in production
    'cache_enabled' => true,
    'cache_ttl' => 3600,
];