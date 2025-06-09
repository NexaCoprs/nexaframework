<?php

/**
 * Configuration du système WebSockets pour Nexa Framework - Phase 3
 * 
 * Ce fichier définit les paramètres de configuration pour les WebSockets,
 * permettant la communication en temps réel entre le serveur et les clients.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation des WebSockets
    |--------------------------------------------------------------------------
    |
    | Cette option active ou désactive le serveur WebSocket dans son ensemble.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Configuration du serveur
    |--------------------------------------------------------------------------
    |
    | Configuration de base pour le serveur WebSocket.
    |
    */
    'server' => [
        'host' => env('WEBSOCKET_HOST', '127.0.0.1'),
        'port' => env('WEBSOCKET_PORT', 8080),
        'ssl' => env('WEBSOCKET_SSL', false),
        'ssl_cert' => env('WEBSOCKET_SSL_CERT', null),
        'ssl_key' => env('WEBSOCKET_SSL_KEY', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentification
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'authentification des connexions WebSocket.
    |
    */
    'auth' => [
        'enabled' => true,
        'middleware' => [
            // Middleware d'authentification
            // \App\WebSocket\Middleware\AuthMiddleware::class,
        ],
        'token_header' => 'Authorization',
        'token_prefix' => 'Bearer ',
    ],

    /*
    |--------------------------------------------------------------------------
    | Canaux (Channels)
    |--------------------------------------------------------------------------
    |
    | Configuration des canaux WebSocket disponibles.
    |
    */
    'channels' => [
        'public' => [
            // Canaux publics accessibles sans authentification
            'general',
            'notifications',
        ],
        'private' => [
            // Canaux privés nécessitant une authentification
            'user.{id}',
            'admin',
        ],
        'presence' => [
            // Canaux de présence (qui peut voir qui est en ligne)
            'chat.{room}',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Gestionnaires d'événements
    |--------------------------------------------------------------------------
    |
    | Définit les gestionnaires pour les différents événements WebSocket.
    |
    */
    'handlers' => [
        'connection' => \App\WebSocket\Handlers\ConnectionHandler::class,
        'disconnection' => \App\WebSocket\Handlers\DisconnectionHandler::class,
        'message' => \App\WebSocket\Handlers\MessageHandler::class,
        'error' => \App\WebSocket\Handlers\ErrorHandler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Limites de connexion
    |--------------------------------------------------------------------------
    |
    | Configuration des limites pour les connexions WebSocket.
    |
    */
    'limits' => [
        'max_connections' => 1000,
        'max_connections_per_ip' => 10,
        'max_message_size' => 1024 * 1024, // 1MB
        'rate_limit' => [
            'messages_per_minute' => 60,
            'connections_per_minute' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat
    |--------------------------------------------------------------------------
    |
    | Configuration pour le système de heartbeat (ping/pong).
    |
    */
    'heartbeat' => [
        'enabled' => true,
        'interval' => 30, // Intervalle en secondes
        'timeout' => 60,  // Timeout en secondes
    ],

    /*
    |--------------------------------------------------------------------------
    | Clustering
    |--------------------------------------------------------------------------
    |
    | Configuration pour le clustering de serveurs WebSocket.
    |
    */
    'clustering' => [
        'enabled' => false,
        'driver' => 'redis', // redis, database
        'redis' => [
            'connection' => 'default',
            'prefix' => 'websocket:',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration pour les logs des WebSockets.
    |
    */
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'channel' => 'websocket',
        'log_connections' => true,
        'log_messages' => false, // Attention: peut générer beaucoup de logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Compression
    |--------------------------------------------------------------------------
    |
    | Configuration pour la compression des messages WebSocket.
    |
    */
    'compression' => [
        'enabled' => true,
        'threshold' => 1024, // Compresser les messages > 1KB
        'level' => 6, // Niveau de compression (1-9)
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS
    |--------------------------------------------------------------------------
    |
    | Configuration CORS pour les connexions WebSocket.
    |
    */
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_headers' => ['*'],
    ],
];