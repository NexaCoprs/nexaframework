<?php

return [
    'name' => 'Nexa Framework',
    'env' => 'development',
    'debug' => true,
    'providers' => [
        Nexa\Database\DatabaseServiceProvider::class,
        Nexa\View\ViewServiceProvider::class,
        Nexa\Routing\RoutingServiceProvider::class,
    ],
];