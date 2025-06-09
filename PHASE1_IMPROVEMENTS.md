# Nexa Framework - Améliorations Phase 1

Ce document détaille les améliorations apportées au framework Nexa dans le cadre de la Phase 1 du plan de développement.

## 🚀 Nouvelles Fonctionnalités Implémentées

### 1. Système de Validation Robuste

#### Classes créées :
- `src/Nexa/Validation/Validator.php` - Classe principale de validation
- `src/Nexa/Validation/ValidatesRequests.php` - Trait pour les contrôleurs
- `src/Nexa/Validation/ValidationException.php` - Exception pour les erreurs de validation

#### Fonctionnalités :
- Validation des champs requis, email, longueur min/max
- Messages d'erreur personnalisables
- Intégration facile dans les contrôleurs via le trait `ValidatesRequests`

#### Exemple d'utilisation :
```php
class WelcomeController
{
    use ValidatesRequests;
    
    public function contact(Request $request)
    {
        $validatedData = $this->validate($request, [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'message' => 'required|min:10|max:1000'
        ]);
        
        // Traitement des données validées
    }
}
```

### 2. Système de Middleware

#### Classes créées :
- `src/Nexa/Http/Middleware/MiddlewareInterface.php` - Interface pour les middlewares
- `src/Nexa/Http/Middleware/VerifyCsrfToken.php` - Protection CSRF
- `src/Nexa/Http/Middleware/AuthMiddleware.php` - Authentification

#### Fonctionnalités :
- Protection CSRF automatique
- Système d'authentification basé sur les sessions
- Interface standardisée pour créer des middlewares personnalisés

### 3. Classe Request Améliorée

#### Fichier créé :
- `src/Nexa/Http/Request.php` - Gestion avancée des requêtes HTTP

#### Fonctionnalités :
- Accès facile aux données GET, POST, FILES
- Détection du type de requête (JSON, AJAX)
- Validation des tokens CSRF
- Méthodes utilitaires pour la manipulation des données

### 4. Relations de Base de Données

#### Classes créées :
- `src/Nexa/Database/QueryBuilder.php` - Constructeur de requêtes avancé
- `src/Nexa/Database/Relations/Relation.php` - Classe de base pour les relations
- `src/Nexa/Database/Relations/HasOneRelation.php` - Relations un-à-un
- `src/Nexa/Database/Relations/HasManyRelation.php` - Relations un-à-plusieurs
- `src/Nexa/Database/Relations/BelongsToRelation.php` - Relations appartient-à
- `src/Nexa/Database/Relations/BelongsToManyRelation.php` - Relations plusieurs-à-plusieurs

#### Fonctionnalités :
- Support complet des relations Eloquent-style
- Query Builder avec méthodes chaînables
- Lazy loading et eager loading
- Gestion des tables pivot pour les relations many-to-many

#### Exemple d'utilisation :
```php
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
}

// Utilisation
$user = User::find(1);
$posts = $user->posts()->where('published', true)->get();
```

### 5. Système de Configuration Avancé

#### Fichier créé :
- `src/Nexa/Core/Config.php` - Gestionnaire de configuration centralisé

#### Fonctionnalités :
- Chargement automatique des fichiers de configuration
- Support des variables d'environnement
- Accès par notation pointée (ex: `Config::get('app.debug')`)
- Rechargement à chaud des configurations

### 6. Système de Logging

#### Fichier créé :
- `src/Nexa/Core/Logger.php` - Système de logging complet

#### Fonctionnalités :
- Support de tous les niveaux PSR-3 (debug, info, warning, error, etc.)
- Fichiers de log séparés par type
- Logging automatique des exceptions et requêtes
- Nettoyage automatique des anciens logs
- Context et métadonnées pour chaque log

#### Exemple d'utilisation :
```php
Logger::info('User logged in', ['user_id' => 123]);
Logger::error('Database connection failed');
Logger::exception($exception);
```

### 7. Système de Cache

#### Fichier créé :
- `src/Nexa/Core/Cache.php` - Système de cache basé sur les fichiers

#### Fonctionnalités :
- Cache avec TTL (Time To Live)
- Méthodes `remember` et `rememberForever`
- Opérations d'incrémentation/décrémentation
- Nettoyage automatique des fichiers expirés
- Statistiques de cache

#### Exemple d'utilisation :
```php
// Cache simple
Cache::put('user_data', $userData, 3600);
$userData = Cache::get('user_data');

// Cache avec callback
$expensiveData = Cache::remember('expensive_operation', function() {
    return performExpensiveOperation();
}, 3600);
```

## 📁 Structure des Fichiers Ajoutés

```
src/Nexa/
├── Core/
│   ├── Config.php
│   ├── Logger.php
│   └── Cache.php
├── Http/
│   ├── Request.php
│   └── Middleware/
│       ├── MiddlewareInterface.php
│       ├── VerifyCsrfToken.php
│       └── AuthMiddleware.php
├── Validation/
│   ├── Validator.php
│   ├── ValidatesRequests.php
│   └── ValidationException.php
└── Database/
    ├── QueryBuilder.php
    └── Relations/
        ├── Relation.php
        ├── HasOneRelation.php
        ├── HasManyRelation.php
        ├── BelongsToRelation.php
        └── BelongsToManyRelation.php

config/
├── logging.php
└── cache.php

resources/views/
└── contact.nx

storage/
├── logs/
└── cache/
```

## 🔧 Configurations Ajoutées

### Variables d'environnement recommandées :
```env
# Logging
LOG_LEVEL=info
LOG_CLEANUP_DAYS=30

# Cache
CACHE_DRIVER=file
CACHE_PREFIX=nexa_
CACHE_DEFAULT_TTL=3600

# Debug
APP_DEBUG=true
```

## 🎯 Améliorations de l'Application

### Classe Application mise à jour :
- Initialisation automatique des services Config, Logger et Cache
- Gestion d'erreurs améliorée avec logging
- Support des variables d'environnement

### Contrôleur WelcomeController amélioré :
- Utilisation du système de cache pour les données de la page d'accueil
- Logging des accès aux pages
- Exemple complet de validation avec la page de contact

### Nouvelle page de contact :
- Formulaire avec validation côté serveur
- Affichage des erreurs de validation
- Design responsive avec Tailwind CSS

## 🚦 Comment Tester les Nouvelles Fonctionnalités

1. **Validation** : Visitez `/contact` et soumettez le formulaire avec des données invalides
2. **Cache** : Les données de la page d'accueil sont mises en cache pendant 1 heure
3. **Logging** : Vérifiez les fichiers dans `storage/logs/` après navigation
4. **Configuration** : Modifiez les fichiers dans `config/` et observez les changements

## 📈 Prochaines Étapes (Phase 2)

Les améliorations de la Phase 1 préparent le terrain pour :
- API REST avec authentification JWT
- Système d'événements et listeners
- Queue system pour les tâches asynchrones
- Tests automatisés
- CLI avancée

## 🤝 Contribution

Ces améliorations suivent les meilleures pratiques PHP et sont conçues pour être extensibles. Chaque composant peut être étendu ou remplacé selon les besoins du projet.