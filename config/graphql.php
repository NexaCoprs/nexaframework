<?php

/**
 * Configuration du système GraphQL pour Nexa Framework - Phase 3
 * 
 * Ce fichier définit les paramètres de configuration pour l'API GraphQL,
 * permettant de créer des API flexibles et performantes.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation de GraphQL
    |--------------------------------------------------------------------------
    |
    | Cette option active ou désactive l'API GraphQL dans son ensemble.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Route GraphQL
    |--------------------------------------------------------------------------
    |
    | Cette option définit l'URL à laquelle l'API GraphQL sera accessible.
    | Par défaut, l'API est disponible à /graphql.
    |
    */
    'route' => '/graphql',

    /*
    |--------------------------------------------------------------------------
    | Route GraphiQL
    |--------------------------------------------------------------------------
    |
    | Cette option définit l'URL à laquelle l'interface GraphiQL sera accessible.
    | GraphiQL est une interface interactive pour tester les requêtes GraphQL.
    | Définir à null pour désactiver GraphiQL.
    |
    */
    'graphiql' => '/graphiql',

    /*
    |--------------------------------------------------------------------------
    | Schémas
    |--------------------------------------------------------------------------
    |
    | Cette option définit les schémas GraphQL disponibles dans l'application.
    | Vous pouvez définir plusieurs schémas pour différentes parties de votre API.
    |
    */
    'schemas' => [
        'default' => [
            'query' => [
                // Définir ici vos types de requêtes
                // 'users' => App\GraphQL\Queries\UsersQuery::class,
            ],
            'mutation' => [
                // Définir ici vos types de mutations
                // 'createUser' => App\GraphQL\Mutations\CreateUserMutation::class,
            ],
            'middleware' => [
                // Middleware appliqué à ce schéma
                // \App\Http\Middleware\Authenticate::class,
            ],
        ],
        // Vous pouvez définir d'autres schémas ici
        // 'admin' => [...],
    ],

    /*
    |--------------------------------------------------------------------------
    | Types
    |--------------------------------------------------------------------------
    |
    | Cette option définit les types GraphQL disponibles dans l'application.
    | Ces types peuvent être utilisés dans plusieurs schémas.
    |
    */
    'types' => [
        // 'User' => App\GraphQL\Types\UserType::class,
        // 'Post' => App\GraphQL\Types\PostType::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-découverte
    |--------------------------------------------------------------------------
    |
    | Lorsque cette option est activée, Nexa recherchera automatiquement les types,
    | requêtes et mutations dans les répertoires spécifiés.
    |
    */
    'auto_discover' => [
        'enabled' => true,
        'directories' => [
            'types' => app_path('GraphQL/Types'),
            'queries' => app_path('GraphQL/Queries'),
            'mutations' => app_path('GraphQL/Mutations'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Configuration pour la mise en cache du schéma GraphQL.
    |
    */
    'cache' => [
        'enabled' => env('APP_ENV') === 'production',
        'ttl' => 3600, // Durée de vie du cache en secondes
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Configuration pour la validation des requêtes GraphQL.
    |
    */
    'validation' => [
        'enabled' => true,
        'rules' => [
            // Règles de validation globales
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Configuration pour la pagination des résultats GraphQL.
    |
    */
    'pagination' => [
        'default_limit' => 15,
        'max_limit' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration pour la gestion des erreurs GraphQL.
    |
    */
    'error_handling' => [
        'debug' => env('APP_DEBUG', false),
        'include_trace' => env('APP_DEBUG', false),
        'include_exception' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Configuration pour la sécurité de l'API GraphQL.
    |
    */
    'security' => [
        'limit_query_complexity' => true,
        'max_query_complexity' => 100,
        'limit_query_depth' => true,
        'max_query_depth' => 10,
        'disable_introspection' => env('APP_ENV') === 'production',
    ],
];