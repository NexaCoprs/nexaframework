# Structure du Projet Nexa Framework

## Organisation des Dossiers

```
nexframework/
├── 📁 app/                    # Application principale
│   ├── Http/                  # Contrôleurs HTTP
│   ├── Models/                # Modèles de données
│   └── WebSocket/             # Gestionnaires WebSocket
│
├── 📁 config/                 # Fichiers de configuration
│   ├── app.php               # Configuration principale
│   ├── database.php          # Configuration base de données
│   ├── production.php        # Configuration production
│   └── ...
│
├── 📁 database/               # Base de données
│   └── migrations/            # Migrations de schéma
│
├── 📁 docs/                   # Documentation
│   ├── API_DOCUMENTATION.md  # Documentation API
│   ├── QUICK_START.md        # Guide de démarrage
│   └── ...
│
├── 📁 examples/               # Exemples d'utilisation
│   ├── complete_app.php      # Application complète
│   ├── simple_demo.php       # Démonstration simple
│   └── ...
│
├── 📁 public/                 # Dossier web public (SEUL ACCESSIBLE WEB)
│   ├── .htaccess             # Configuration Apache
│   ├── web.config            # Configuration IIS
│   ├── index.php             # Point d'entrée
│   ├── assets/               # Ressources statiques (CSS, JS, images)
│   ├── css/                  # Feuilles de style
│   ├── js/                   # Scripts JavaScript
│   └── uploads/              # Fichiers uploadés par les utilisateurs
│
├── 📁 resources/              # Ressources de l'application
│   └── views/                # Templates de vues
│
├── 📁 routes/                 # Définition des routes
│   └── web.php               # Routes web
│
├── 📁 scripts/                # Scripts utilitaires
│   └── cleanup.php           # Script de nettoyage
│
├── 📁 src/                    # Code source du framework
│   ├── Nexa/                 # Core du framework
│   ├── GraphQL/              # Fonctionnalités GraphQL
│   ├── Microservices/        # Support microservices
│   └── ...
│
├── 📁 storage/                # Stockage de l'application
│   ├── cache/                # Cache de l'application
│   ├── logs/                 # Fichiers de logs
│   └── framework/            # Cache du framework
│
├── 📁 tests/                  # Tests automatisés
│   ├── AuthTest.php          # Tests d'authentification
│   ├── GraphQLTest.php       # Tests GraphQL
│   └── ...
│
├── 📁 vendor/                 # Dépendances Composer
│
├── .env                       # Variables d'environnement
├── .htaccess                  # Redirection vers public/
├── index_redirect.php         # Alternative de redirection
├── composer.json              # Configuration Composer
├── DEPLOYMENT.md              # Guide de déploiement
└── README.md                  # Documentation principale
```

## Fichiers Importants

### Configuration
- **`.env`** : Variables d'environnement
- **`config/app.php`** : Configuration principale
- **`config/production.php`** : Configuration pour la production

### Déploiement
- **`.htaccess`** (racine) : Redirection vers public/
- **`public/.htaccess`** : Réécriture d'URL Apache
- **`public/web.config`** : Configuration IIS
- **`DEPLOYMENT.md`** : Instructions de déploiement

### Développement
- **`composer.json`** : Dépendances PHP
- **`scripts/cleanup.php`** : Nettoyage du projet
- **`examples/`** : Exemples d'utilisation

## Commandes Utiles

```bash
# Installer les dépendances
composer install

# Nettoyer le projet
php scripts/cleanup.php

# Lancer les tests
php tests/SimpleTestSuite.php
```

## Sécurité et Accès Web

### ⚠️ IMPORTANT - Dossiers accessibles :

**✅ ACCESSIBLE depuis le web :**
- `public/` - Seul dossier qui doit être accessible
- `public/uploads/` - Fichiers uploadés par les utilisateurs
- `public/assets/`, `public/css/`, `public/js/` - Ressources statiques

**❌ NON ACCESSIBLE depuis le web :**
- `examples/` - Exemples de code (sécurité)
- `config/` - Configuration (contient des secrets)
- `src/` - Code source du framework
- `storage/` - Logs et cache
- `app/` - Code de l'application
- Tous les autres dossiers à la racine

### 🔄 Distinction importante :
- **Dossier `examples/`** = Code PHP d'exemples (non accessible web)
- **Route `/examples`** = Page web qui affiche la vue `resources/views/examples.nx`

## Notes

- Le dossier `public/` doit être configuré comme racine web
- Les fichiers `.htaccess` gèrent la redirection et la réécriture d'URL
- La configuration de production est séparée dans `config/production.php`
- Les logs sont stockés dans `storage/logs/`
- Permissions : `public/uploads/` doit être en écriture (755 ou 775)