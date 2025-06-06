<?php

/**
 * Configuration du système de modules pour Nexa Framework - Phase 3
 * 
 * Ce fichier définit les paramètres de configuration pour l'architecture modulaire,
 * permettant de structurer l'application en modules indépendants et réutilisables.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation du système de modules
    |--------------------------------------------------------------------------
    |
    | Cette option active ou désactive le système de modules dans son ensemble.
    | Lorsque désactivé, l'application fonctionnera comme une application monolithique.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Répertoire des modules
    |--------------------------------------------------------------------------
    |
    | Ce chemin est utilisé pour stocker les modules de l'application. Par défaut,
    | les modules sont stockés dans le dossier 'modules' à la racine du projet.
    |
    */
    'directory' => base_path('modules'),

    /*
    |--------------------------------------------------------------------------
    | Auto-découverte
    |--------------------------------------------------------------------------
    |
    | Lorsque cette option est activée, Nexa recherchera automatiquement les modules
    | dans le répertoire spécifié et les chargera s'ils sont correctement structurés.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Modules activés
    |--------------------------------------------------------------------------
    |
    | Cette liste contient tous les modules qui doivent être activés au démarrage.
    | Les modules peuvent être désactivés ici même s'ils sont installés.
    |
    */
    'modules' => [
        // 'User' => true,
        // 'Blog' => true,
        // 'Shop' => false, // Module installé mais désactivé
    ],

    /*
    |--------------------------------------------------------------------------
    | Ordre de chargement
    |--------------------------------------------------------------------------
    |
    | Définit l'ordre dans lequel les modules sont chargés. Ceci est important
    | pour gérer les dépendances entre modules.
    |
    */
    'load_order' => [
        // Modules de base (chargés en premier)
        // 'Core',
        // 'User',
        
        // Modules fonctionnels
        // 'Blog',
        // 'Shop',
        
        // Modules d'extension (chargés en dernier)
        // 'Analytics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Structure des modules
    |--------------------------------------------------------------------------
    |
    | Définit la structure de dossiers attendue pour chaque module.
    | Ces dossiers seront automatiquement créés lors de la génération d'un module.
    |
    */
    'structure' => [
        'controllers' => true,
        'models' => true,
        'views' => true,
        'routes' => true,
        'migrations' => true,
        'config' => true,
        'services' => true,
        'tests' => true,
        'assets' => true,
        'lang' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Isolation des modules
    |--------------------------------------------------------------------------
    |
    | Détermine si les modules doivent être isolés les uns des autres.
    | Lorsque activé, les modules ne peuvent pas accéder directement aux
    | classes et fonctionnalités des autres modules.
    |
    */
    'isolation' => [
        'enabled' => false,
        'exceptions' => [
            // Liste des modules pouvant accéder à d'autres modules
            // 'Core' => ['*'], // Le module Core peut accéder à tous les modules
            // 'Shop' => ['User', 'Payment'], // Shop peut accéder à User et Payment
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Espaces de noms
    |--------------------------------------------------------------------------
    |
    | Préfixe d'espace de noms pour les modules. Chaque module sera chargé
    | sous cet espace de noms suivi du nom du module.
    |
    */
    'namespace' => 'App\Modules',

    /*
    |--------------------------------------------------------------------------
    | Préfixe d'URL
    |--------------------------------------------------------------------------
    |
    | Préfixe d'URL pour les routes des modules. Par défaut, les routes d'un module
    | seront préfixées par le nom du module en minuscules.
    |
    */
    'url_prefix' => true,

    /*
    |--------------------------------------------------------------------------
    | Gestion des assets
    |--------------------------------------------------------------------------
    |
    | Configuration pour la gestion des assets (CSS, JS, images) des modules.
    |
    */
    'assets' => [
        'publish' => true, // Publier automatiquement les assets des modules
        'destination' => public_path('modules'), // Destination des assets publiés
    ],

    /*
    |--------------------------------------------------------------------------
    | Gestion des migrations
    |--------------------------------------------------------------------------
    |
    | Configuration pour la gestion des migrations des modules.
    |
    */
    'migrations' => [
        'run_on_enable' => true, // Exécuter les migrations lors de l'activation d'un module
        'rollback_on_disable' => false, // Annuler les migrations lors de la désactivation
    ],
];