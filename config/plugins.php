<?php

/**
 * Configuration du système de plugins pour Nexa Framework - Phase 3
 * 
 * Ce fichier définit les paramètres de configuration pour le système de plugins,
 * incluant les plugins activés, leurs dépendances, et les paramètres de la marketplace.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Activation du système de plugins
    |--------------------------------------------------------------------------
    |
    | Cette option active ou désactive le système de plugins dans son ensemble.
    | Lorsque désactivé, aucun plugin ne sera chargé, quelle que soit sa configuration.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Répertoire des plugins
    |--------------------------------------------------------------------------
    |
    | Ce chemin est utilisé pour stocker les plugins installés. Par défaut,
    | les plugins sont stockés dans le dossier 'plugins' à la racine du projet.
    |
    */
    'directory' => base_path('plugins'),

    /*
    |--------------------------------------------------------------------------
    | Auto-découverte
    |--------------------------------------------------------------------------
    |
    | Lorsque cette option est activée, Nexa recherchera automatiquement les plugins
    | dans le répertoire spécifié et les chargera s'ils sont correctement structurés.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Plugins activés
    |--------------------------------------------------------------------------
    |
    | Cette liste contient tous les plugins qui doivent être activés au démarrage.
    | Les plugins peuvent être désactivés ici même s'ils sont installés.
    |
    */
    'plugins' => [
        // 'example-plugin' => true,
        // 'another-plugin' => false, // Plugin installé mais désactivé
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace
    |--------------------------------------------------------------------------
    |
    | Configuration pour la connexion à la marketplace de plugins Nexa.
    |
    */
    'marketplace' => [
        'enabled' => true,
        'url' => 'https://marketplace.nexaframework.com/api/v1',
        'cache_ttl' => 3600, // Durée de mise en cache des données de la marketplace (en secondes)
        'verify_signatures' => true, // Vérifier les signatures des plugins téléchargés
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks système
    |--------------------------------------------------------------------------
    |
    | Points d'extension où les plugins peuvent s'intégrer au framework.
    | Ces hooks sont automatiquement déclenchés aux moments appropriés.
    |
    */
    'hooks' => [
        'boot' => true,      // Au démarrage de l'application
        'request' => true,   // À chaque requête
        'response' => true,  // Avant l'envoi de la réponse
        'shutdown' => true,  // À la fermeture de l'application
        'cli' => true,       // Lors de l'exécution en mode CLI
    ],

    /*
    |--------------------------------------------------------------------------
    | Sécurité des plugins
    |--------------------------------------------------------------------------
    |
    | Options de sécurité pour l'exécution des plugins.
    |
    */
    'security' => [
        'sandbox' => true,           // Exécuter les plugins dans un environnement isolé
        'permissions' => [           // Permissions accordées aux plugins
            'filesystem' => false,   // Accès au système de fichiers
            'database' => true,      // Accès à la base de données
            'network' => false,      // Accès réseau
            'session' => true,       // Accès aux sessions
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mise à jour automatique
    |--------------------------------------------------------------------------
    |
    | Configuration pour les mises à jour automatiques des plugins.
    |
    */
    'auto_update' => [
        'enabled' => false,           // Activer les mises à jour automatiques
        'check_frequency' => 86400,   // Fréquence de vérification (en secondes, 86400 = 1 jour)
        'notify_only' => true,        // Notifier seulement, sans installer automatiquement
    ],
];