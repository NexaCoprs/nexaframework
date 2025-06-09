<?php

/**
 * Configuration du système de microservices pour Nexa Framework - Phase 3
 * 
 * Ce fichier définit les paramètres de configuration pour l'architecture microservices,
 * permettant de construire des applications distribuées et scalables.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation des microservices
    |--------------------------------------------------------------------------
    |
    | Cette option active ou désactive le système de microservices dans son ensemble.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Service Discovery
    |--------------------------------------------------------------------------
    |
    | Configuration pour la découverte de services.
    |
    */
    'discovery' => [
        'driver' => env('MICROSERVICE_DISCOVERY_DRIVER', 'config'), // config, consul, etcd, redis
        'refresh_interval' => 60, // Intervalle de rafraîchissement en secondes
        'ttl' => 300, // Durée de vie des enregistrements en secondes
        
        // Configuration pour Consul
        'consul' => [
            'host' => env('CONSUL_HOST', 'localhost'),
            'port' => env('CONSUL_PORT', 8500),
            'token' => env('CONSUL_TOKEN'),
            'scheme' => env('CONSUL_SCHEME', 'http'),
        ],
        
        // Configuration pour etcd
        'etcd' => [
            'endpoints' => explode(',', env('ETCD_ENDPOINTS', 'http://localhost:2379')),
            'auth' => [
                'username' => env('ETCD_USERNAME'),
                'password' => env('ETCD_PASSWORD'),
            ],
        ],
        
        // Configuration pour Redis
        'redis' => [
            'connection' => 'default',
            'prefix' => 'microservice:',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Registry
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'enregistrement de ce service.
    |
    */
    'registry' => [
        'service_name' => env('MICROSERVICE_NAME', 'app'),
        'service_id' => env('MICROSERVICE_ID', null), // Généré automatiquement si null
        'service_version' => env('MICROSERVICE_VERSION', '1.0.0'),
        'service_tags' => explode(',', env('MICROSERVICE_TAGS', '')),
        'service_address' => env('MICROSERVICE_ADDRESS', '127.0.0.1'),
        'service_port' => env('MICROSERVICE_PORT', 80),
        'health_check_path' => '/health',
        'health_check_interval' => '10s',
        'auto_register' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Services externes
    |--------------------------------------------------------------------------
    |
    | Liste des services externes avec lesquels ce service peut communiquer.
    | Utilisé uniquement avec le driver 'config'.
    |
    */
    'services' => [
        // 'user-service' => [
        //     'url' => env('USER_SERVICE_URL', 'http://user-service:8000'),
        //     'timeout' => 5.0,
        //     'retry' => 3,
        //     'version' => '1.0',
        // ],
        // 'payment-service' => [
        //     'url' => env('PAYMENT_SERVICE_URL', 'http://payment-service:8000'),
        //     'timeout' => 10.0,
        //     'retry' => 2,
        //     'version' => '1.0',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | Configuration pour le pattern Circuit Breaker qui empêche les appels
    | répétés à des services défaillants.
    |
    */
    'circuit_breaker' => [
        'enabled' => true,
        'threshold' => 5, // Nombre d'échecs avant ouverture du circuit
        'timeout' => 30, // Durée en secondes avant tentative de fermeture
        'half_open_timeout' => 5, // Durée en secondes pour l'état semi-ouvert
        'storage' => 'redis', // redis, file, memory
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | Configuration pour la politique de réessai en cas d'échec d'appel.
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'initial_delay' => 1000, // Délai initial en millisecondes
        'multiplier' => 2.0, // Facteur multiplicateur pour le délai
        'max_delay' => 10000, // Délai maximum en millisecondes
        'jitter' => 0.1, // Facteur de variation aléatoire (0-1)
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Configuration pour le traçage distribué.
    |
    */
    'tracing' => [
        'enabled' => true,
        'driver' => 'jaeger', // jaeger, zipkin
        'service_name' => env('MICROSERVICE_NAME', 'app'),
        
        // Configuration pour Jaeger
        'jaeger' => [
            'host' => env('JAEGER_HOST', 'localhost'),
            'port' => env('JAEGER_PORT', 6831),
            'sampler_type' => 'const', // const, probabilistic, rate_limiting, remote
            'sampler_param' => 1.0,
        ],
        
        // Configuration pour Zipkin
        'zipkin' => [
            'endpoint' => env('ZIPKIN_ENDPOINT', 'http://localhost:9411/api/v2/spans'),
            'sample_rate' => 1.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Gateway
    |--------------------------------------------------------------------------
    |
    | Configuration pour l'API Gateway intégré.
    |
    */
    'api_gateway' => [
        'enabled' => false,
        'routes' => [
            // 'users' => [
            //     'service' => 'user-service',
            //     'prefix' => '/api/users',
            //     'strip_prefix' => true,
            //     'middleware' => ['api', 'auth:api'],
            // ],
            // 'payments' => [
            //     'service' => 'payment-service',
            //     'prefix' => '/api/payments',
            //     'strip_prefix' => true,
            //     'middleware' => ['api', 'auth:api'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Bus
    |--------------------------------------------------------------------------
    |
    | Configuration pour le bus d'événements distribué.
    |
    */
    'event_bus' => [
        'enabled' => true,
        'driver' => 'redis', // redis, rabbitmq, kafka
        
        // Configuration pour Redis
        'redis' => [
            'connection' => 'default',
            'prefix' => 'event_bus:',
        ],
        
        // Configuration pour RabbitMQ
        'rabbitmq' => [
            'host' => env('RABBITMQ_HOST', 'localhost'),
            'port' => env('RABBITMQ_PORT', 5672),
            'user' => env('RABBITMQ_USER', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),
            'vhost' => env('RABBITMQ_VHOST', '/'),
            'exchange' => env('RABBITMQ_EXCHANGE', 'microservices'),
            'exchange_type' => 'topic',
        ],
        
        // Configuration pour Kafka
        'kafka' => [
            'brokers' => explode(',', env('KAFKA_BROKERS', 'localhost:9092')),
            'topic' => env('KAFKA_TOPIC', 'microservices'),
            'group_id' => env('KAFKA_GROUP_ID', 'nexa-microservices'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Checks
    |--------------------------------------------------------------------------
    |
    | Configuration pour les vérifications de santé du service.
    |
    */
    'health_checks' => [
        'enabled' => true,
        'path' => '/health',
        'checks' => [
            'database' => true,
            'cache' => true,
            'storage' => true,
            'queue' => true,
            'external_services' => true,
        ],
    ],
];