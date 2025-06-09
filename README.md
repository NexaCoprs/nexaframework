<<<<<<< HEAD
# 🚀 Nexa Framework

**Un framework PHP moderne et efficace**

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/nexa-framework/nexa)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](https://github.com/nexa-framework/nexa/actions)
[![Documentation](https://img.shields.io/badge/docs-latest-blue.svg)](https://docs.nexa-framework.com)

Nexa Framework est un framework PHP moderne qui simplifie le développement web avec une architecture claire, des outils pratiques, et une approche pragmatique du développement d'applications.

## 📋 Table des Matières

- [🌟 Fonctionnalités Principales](#-fonctionnalités-principales)
- [🆚 Nexa vs Laravel](#-nexa-vs-laravel---révolution-totale)
- [🚀 Démarrage Rapide](#-démarrage-rapide)
- [📖 Documentation](#-documentation)
- [🎯 Fonctionnalités Principales](#-fonctionnalités-principales)
- [🛠️ Extensions VSCode](#️-extensions-vscode)
- [🤝 Contribution](#-contribution)
- [📄 Licence](#-licence)

## 🌟 Fonctionnalités Principales

### 🏗️ Architecture Moderne
- **Auto-découverte** : Détection automatique des contrôleurs et composants
- **Structure claire** : Organisation intuitive des fichiers et dossiers
- **Configuration simple** : Mise en place rapide avec des conventions sensées
- **Injection de dépendances** : Gestion automatique des dépendances

### ⚡ Performance Optimisée
- **Routage efficace** : Système de routage rapide et flexible
- **Cache intelligent** : Mise en cache automatique des éléments coûteux
- **Optimisations intégrées** : Code optimisé pour de meilleures performances
- **Compilation des templates** : Templates compilés pour une exécution rapide

### 🎯 Développement Simplifié
- **Convention over configuration** : Moins de configuration, plus de développement
- **Outils CLI pratiques** : Génération de code et tâches automatisées
- **Validation intégrée** : Système de validation robuste et extensible
- **Gestion d'erreurs** : Gestion claire et informative des erreurs

### 🎨 Templates .nx
- **Syntaxe claire** : Templates faciles à lire et à maintenir
- **Composants réutilisables** : Système de composants modulaires
- **Héritage de templates** : Réutilisation et extension de layouts
- **Sécurité intégrée** : Protection automatique contre les failles XSS

### 🔒 Sécurité Intégrée
- **Protection CSRF** : Protection automatique contre les attaques CSRF
- **Validation des données** : Validation et nettoyage automatique des entrées
- **Authentification** : Système d'authentification flexible et sécurisé
- **Chiffrement** : Outils de chiffrement pour protéger les données sensibles

## 🆚 Nexa vs Autres Frameworks

| Fonctionnalité | Laravel | Symfony | Nexa Framework |
|---|---|---|---|
| **Courbe d'apprentissage** | Moyenne | Élevée | **Faible** 📚 |
| **Performance** | Bonne | Très bonne | **Excellente** ⚡ |
| **Auto-découverte** | Partielle | Limitée | **Complète** 🔍 |
| **Templates** | Blade | Twig | **Templates .nx** 🎨 |
| **Configuration** | Moyenne | Complexe | **Simple** ⚙️ |
| **Documentation** | Excellente | Bonne | **Claire et pratique** 📖 |
| **Écosystème** | Très riche | Riche | **En développement** 🌱 |
| **Communauté** | Très large | Large | **Grandissante** 👥 |
| **Innovation** | Stable | Mature | **Moderne** 🚀 |
| **Flexibilité** | Bonne | Excellente | **Optimale** 🎯 |

## 🚀 Démarrage rapide

```bash
# Installation via Composer
composer create-project nexa/framework mon-projet
cd mon-projet

# Configuration de base
cp .env.example .env
php nexa key:generate

# Migration de la base de données
php nexa migrate

# Démarrage du serveur de développement
php nexa serve
```

### Commandes CLI Utiles

```bash
# Générer un contrôleur
php nexa make:controller UserController

# Générer un modèle
php nexa make:model User

# Créer une migration
php nexa make:migration create_users_table

# Générer un middleware
php nexa make:middleware AuthMiddleware
```

## 📖 Documentation

### 📚 Guides Complets

- **[Guide de Démarrage](docs/GETTING_STARTED.md)** - Installation et premiers pas
- **[Architecture](docs/ARCHITECTURE.md)** - Comprendre l'architecture Nexa
- **[Templates .nx](docs/NX_TEMPLATES.md)** - Guide complet des templates .nx
- **[API Reference](docs/API_REFERENCE.md)** - Documentation complète de l'API
- **[Exemples](docs/EXAMPLES.md)** - Exemples pratiques et cas d'usage

### 🎯 Fonctionnalités Spécifiques

- **[Fonctionnalités Avancées](docs/ADVANCED.md)** - Fonctionnalités avancées
- **[Optimisation](docs/OPTIMIZATION.md)** - Performance et optimisation
- **[Auto-Découverte](docs/AUTO_DISCOVERY.md)** - Système d'auto-découverte
- **[Sécurité](docs/SECURITY.md)** - Sécurité avancée
- **[WebSockets](docs/WEBSOCKETS.md)** - Communication temps réel
- **[GraphQL](docs/GRAPHQL.md)** - API GraphQL native
- **[Microservices](docs/MICROSERVICES.md)** - Architecture microservices
- **[Tests](docs/TESTING.md)** - Framework de tests intégré

### 🛠️ Outils de Développement

- **[CLI Nexa](docs/CLI.md)** - Interface en ligne de commande
- **[Extensions VSCode](docs/VSCODE_EXTENSIONS.md)** - Outils de développement
- **[Débogage](docs/DEBUGGING.md)** - Techniques de débogage
- **[Déploiement](docs/DEPLOYMENT.md)** - Guide de déploiement

## 🎯 Fonctionnalités principales

### 🏗️ Architecture moderne
- **Auto-discovery** : Détection automatique des contrôleurs, modèles et middleware
- **Zero-config** : Fonctionne immédiatement sans configuration
- **Hot-reload** : Rechargement automatique des routes en développement
- **API fluide** : Syntaxe chainable et expressive

### 🛣️ Routage avancé
- **Routes expressives** : Syntaxe claire et intuitive
- **Groupes de routes** : Organisation et middleware partagés
- **Routes de ressources** : CRUD automatique
- **Contraintes de paramètres** : Validation au niveau des routes
- **Routes nommées** : Navigation et génération d'URLs simplifiées

### 🗄️ ORM moderne
- **Query Builder fluide** : Requêtes expressives et chainables
- **Relations éloquentes** : Gestion intuitive des relations
- **Scopes et mutateurs** : Logique métier encapsulée
- **Timestamps automatiques** : Gestion transparente des dates
- **Casting d'attributs** : Conversion automatique des types

### ✅ Validation puissante
- **API fluide** : Validation chainable et expressive
- **Règles extensibles** : Ajout facile de règles personnalisées
- **Messages personnalisés** : Contrôle total des messages d'erreur
- **Validation de tableaux** : Support des structures complexes

### 🚀 Cache intelligent
- **Stores multiples** : File, Array, et extensible
- **API unifiée** : Interface cohérente pour tous les stores
- **Remember patterns** : Cache automatique avec callbacks
- **Nettoyage automatique** : Gestion transparente de l'expiration

### 🎪 Système d'événements
- **Listeners flexibles** : Gestion d'événements découplée
- **Wildcards** : Écoute de patterns d'événements
- **Priorités** : Contrôle de l'ordre d'exécution
- **Subscribers** : Organisation des listeners

### 🛠️ CLI moderne
- **Commandes make** : Génération rapide de code
- **Interface colorée** : Sortie claire et attrayante
- **Validation interactive** : Prompts intelligents
- **Progress bars** : Feedback visuel pour les tâches longues

## 📚 Exemples de code

### Routage simple et élégant

```php
// Routes basiques
Route::get('/', function() {
    return view('welcome');
});

Route::post('/users', [UserController::class, 'store']);

// Groupes de routes avec middleware
Route::group(['prefix' => 'api', 'middleware' => 'auth'], function() {
    Route::resource('posts', PostController::class);
    Route::get('/profile', [UserController::class, 'profile']);
});
```

### ORM expressif et puissant

```php
// Modèle simple
class User extends Model
{
    protected $fillable = ['name', 'email'];
    protected $casts = ['email_verified_at' => 'datetime'];
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Requêtes fluides
$users = User::where('active', true)
    ->whereNotNull('email_verified_at')
    ->with('posts')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Création et mise à jour
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$user = User::firstOrCreate(
    ['email' => 'jane@example.com'],
    ['name' => 'Jane Doe']
);
```

### Validation fluide et expressive

```php
// Dans un contrôleur
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8|confirmed',
        'age' => 'integer|min:18'
    ]);
    
    return User::create($validated);
}

// Validation avec middleware
Route::post('/users', [UserController::class, 'store'])
    ->middleware(ValidationMiddleware::make([
        'name' => 'required|string',
        'email' => 'required|email'
    ]));
```

### Cache intelligent

```php
// Cache simple
Cache::put('key', 'value', 3600); // 1 heure
$value = Cache::get('key', 'default');

// Remember pattern
$users = Cache::remember('active_users', 3600, function() {
    return User::where('active', true)->get();
});

// Cache permanent
Cache::forever('settings', $settings);
```

### Système d'événements

```php
// Déclencher un événement
Event::dispatch('user.created', $user);

// Écouter un événement
Event::listen('user.created', function($user) {
    // Envoyer un email de bienvenue
    Mail::send('welcome', $user);
});

// Wildcards
Event::listen('user.*', function($event, $data) {
    Log::info("Événement utilisateur: {$event}");
});
```

## 🛠️ Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Extensions PHP : PDO, mbstring, openssl

### Installation via Composer

```bash
# Nouveau projet
composer create-project nexa/framework mon-projet
cd mon-projet

# Configuration
cp .env.example .env
php nexa key:generate

# Base de données (optionnel)
php nexa migrate

# Démarrage
php nexa serve
```

### Installation manuelle

```bash
git clone https://github.com/nexa/framework.git
cd framework
composer install
cp .env.example .env
php nexa key:generate
```

## 🚀 Utilisation

### Structure du projet

```
mon-projet/
├── app/
│   ├── Controllers/     # Contrôleurs
│   ├── Models/         # Modèles
│   └── Middleware/     # Middleware personnalisés
├── config/             # Configuration
├── public/             # Point d'entrée web
├── resources/
│   ├── views/          # Templates
│   └── assets/         # Assets (CSS, JS)
├── routes/             # Définition des routes
├── storage/            # Fichiers générés
└── vendor/             # Dépendances
```

### Commandes CLI Pratiques

#### Génération de Code
```bash
# Créer un contrôleur avec méthodes CRUD
php nexa make:controller ProductController --resource

# Générer un modèle avec migration
php nexa make:model Product --migration

# Créer un middleware personnalisé
php nexa make:middleware AuthMiddleware

# Générer un handler pour API
php nexa make:handler ApiHandler
```

#### Gestion de la Base de Données
```bash
# Créer une migration
php nexa make:migration create_products_table

# Exécuter les migrations
php nexa migrate

# Rollback des migrations
php nexa migrate:rollback

# Seeder la base de données
php nexa db:seed
```

#### Outils de Développement
```bash
# Démarrer le serveur de développement
php nexa serve

# Nettoyer le cache
php nexa cache:clear

# Optimiser l'application
php nexa optimize

# Lancer les tests
php nexa test
```
=======
# Nexa Framework

Nexa est un framework PHP moderne, léger et puissant pour le développement d'applications web et d'APIs.

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🚀 Aperçu

Nexa Framework est conçu pour offrir une expérience de développement fluide tout en maintenant des performances exceptionnelles. Il combine la simplicité d'utilisation avec des fonctionnalités avancées pour répondre aux besoins des applications modernes.
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77

### ✨ Fonctionnalités Principales

#### Phase 1 - Fondations ✅
- 🗄️ **ORM avancé** avec relations, migrations et seeding
- 🛣️ **Routage intuitif** avec support pour les groupes et middlewares
- 🔄 **Contrôleurs** avec injection de dépendances
- 🖥️ **Moteur de templates** rapide et flexible
- 🔍 **Query Builder** fluide et expressif
- ✅ **Validation** des données robuste
- 🔒 **Middleware** pour la sécurité et plus
- 📦 **Cache** haute performance
- 📝 **Logging** compatible PSR-3

#### Phase 2 - Fonctionnalités Avancées ✅ NOUVEAU!
- 🔐 **Authentification JWT** complète avec refresh tokens
- 📡 **Système d'événements** avec listeners et priorités
- 🔄 **Files d'attente (Queue)** pour le traitement asynchrone
- 🧪 **Framework de tests** automatisés avec assertions
- 💻 **Interface CLI** pour la gestion et génération de code
- 🛡️ **Sécurité avancée** (CORS, CSRF, Rate Limiting)
- 📈 **Monitoring et performance** intégrés

> **🎉 Phase 2 Complète!** Toutes les fonctionnalités avancées sont maintenant disponibles et testées.

#### Phase 3 - Écosystème Complet 🚧 EN COURS
- 🔌 **Architecture modulaire** avec système de plugins
- 📊 **Support GraphQL** avec génération automatique de schémas
- 🔄 **Websockets** pour communication en temps réel
- 🌐 **Architecture microservices** avec service discovery
- 🛠️ **Outils de développement avancés** (debugging, profiling)

> **🚀 Phase 3 Démarrée!** Nous commençons le développement de l'écosystème complet.

## 🏃‍♂️ Démarrage Rapide

### Installation

1. Clonez le repository :
```bash
git clone https://github.com/votre-username/nexa-framework.git
cd nexa-framework
```

2. Installez les dépendances :
```bash
composer install
```

3. Configurez votre environnement :
```bash
cp .env.example .env
# Éditez le fichier .env avec vos paramètres
```

4. Nettoyez et organisez le projet :
```bash
php scripts/cleanup.php
```

5. Lancez le serveur de développement :
```bash
php -S localhost:8000 -t public
```

## Documentation

- 📁 [Structure du Projet](PROJECT_STRUCTURE.md) - Organisation des fichiers
- 🚀 [Guide de Déploiement](DEPLOYMENT.md) - Instructions pour OVH
- 🔒 [Guide de Sécurité](SECURITY.md) - Configuration sécurisée
- 📚 [Documentation API](docs/API_DOCUMENTATION.md) - Référence API
- ⚡ [Démarrage Rapide](docs/QUICK_START.md) - Guide de démarrage

<<<<<<< HEAD
### Architecture Moderne

#### Structure du Projet
```
nexa-framework/
├── kernel/           # Cœur du framework (ancien src/)
├── workspace/        # Votre espace de travail
│   ├── entities/     # Entités auto-découvertes
│   ├── handlers/     # Handlers de requêtes
│   ├── services/     # Services métier
│   └── migrations/   # Migrations de base de données
├── flows/           # Flux de données (ancien routes/)
├── interface/       # Templates .nx
├── assets/          # Ressources statiques
└── storage/         # Stockage des données
```

#### Entité Auto-Découverte
```php
// workspace/entities/User.php
#[AutoDiscover, Cache('users'), Validate, Secure]
class User extends Entity
{
    #[HasMany(Task::class)]
    public function tasks() { return $this->hasMany(Task::class); }
    
    #[Intelligent]
    public function getPerformanceScore() {
        return $this->ai()->calculateScore();
    }
}
```

#### Handler Intelligent
```php
// workspace/handlers/UserHandler.php
#[AutoRoute('/api/users'), Middleware('auth'), Cache, Secure]
class UserHandler extends Handler
{
    #[Get('/'), Paginate, Cache(300)]
    public function index() {
        return User::quantum()->paginate();
    }
    
    #[Post('/'), Validate(UserRequest::class), Audit]
    public function store(UserRequest $request) {
        return User::quantum()->create($request->validated());
    }
}
```

#### Template .nx
```html
<!-- interface/UserDashboard.nx -->
@cache('user-dashboard', 300)
@entity(User::class)
@handler(UserHandler::class)

<div class="dashboard" nx:reactive>
    <nx:navigation />
    
    <div class="stats-grid">
        @foreach($stats as $stat)
            <nx:stat-card 
                :title="$stat.title" 
                :value="$stat.value" 
                :trend="$stat.trend" 
                :color="$stat.color" />
        @endforeach
    </div>
    
    <div class="projects">
        @if($projects->count() > 0)
            @foreach($projects as $project)
                <nx:project-card :project="$project" />
            @endforeach
        @else
            <nx:empty-state message="Aucun projet trouvé" />
        @endif
    </div>
    
    @realtime('user-updates')
    <nx:notification-center />
</div>

<script>
export default {
    data: () => ({
        reactive: true,
        realtime: true
    }),
    
    computed: {
        totalProjects() {
            return this.projects.length;
        }
    },
    
    methods: {
        refreshData() {
            this.$quantum.refresh();
        }
    }
}
</script>
=======
### Exemple de Routage

```php
// routes/web.php
use Nexa\Routing\Router;

Router::get('/', function() {
    return view('welcome');
});

Router::get('/users/{id}', 'UserController@show');

Router::group(['prefix' => 'admin', 'middleware' => 'auth'], function() {
    Router::get('/dashboard', 'AdminController@dashboard');
});
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77
```

### Exemple d'Authentification JWT

```php
// Génération d'un token JWT
$token = \Nexa\Auth\JWT::generate([
    'user_id' => 1,
    'role' => 'admin'
]);

// Vérification d'un token
$payload = \Nexa\Auth\JWT::verify($token);

// Utilisation du middleware JWT
Router::group(['middleware' => 'jwt'], function() {
    Router::get('/profile', 'UserController@profile');
});
```

### Exemple d'Événements

```php
// Utilisation des événements prédéfinis
use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Events\ModelCreated;

// Instancier un événement avec des données
$event = new UserRegistered($user);

// Accéder aux données de l'événement
$userId = $event->user->id;
$email = $event->user->email;

// Événement de connexion
$loginEvent = new UserLoggedIn($user, $request->ip());

// Événement de création de modèle
$modelEvent = new ModelCreated($post, 'Post');
$modelName = $modelEvent->modelType; // 'Post'
```

### Exemple de Queue

```php
// Création d'un job
$job = new \Nexa\Queue\Job('App\Jobs\SendEmail', [
    'user_id' => 123,
    'subject' => 'Bienvenue!',
    'content' => 'Merci de votre inscription.'
]);

// Ajout à la queue pour exécution immédiate
\Nexa\Queue\Queue::push($job);

// Ajout à la queue pour exécution différée (60 secondes)
\Nexa\Queue\Queue::later($job, 60);
```

## ✅ Tests et Validation Phase 2

La Phase 2 a été validée avec succès via le script `test_phase2.php` qui vérifie toutes les nouvelles fonctionnalités :

```
✅ Test JWT Authentication: PASSED
✅ Test Event System: PASSED
✅ Test Queue System: PASSED
✅ Test CLI Commands: PASSED
✅ Test Advanced Security: PASSED

All Phase 2 tests passed successfully!
```

### Composants validés :

- ✓ Authentification JWT avec refresh tokens
- ✓ Système d'événements avec listeners prioritaires
- ✓ Queue system avec drivers Database et Sync
- ✓ Interface CLI avec commandes de génération
- ✓ Sécurité avancée (CORS, Rate Limiting)

### Corrections Récentes :

- ✓ Correction du namespace des événements prédéfinis
- ✓ Amélioration de la gestion des erreurs dans les queues
- ✓ Optimisation des performances du dispatcher d'événements
- ✓ Correction des tests automatisés pour PHP 8.1+

## 📁 Structure du Projet

```
├── app/                     # Code de l'application
│   ├── Controllers/         # Contrôleurs
│   ├── Models/              # Modèles
│   ├── Middleware/          # Middlewares personnalisés
│   ├── Events/              # Événements personnalisés
│   └── Jobs/                # Jobs pour les queues
├── config/                  # Configuration
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   └── queue.php
├── database/                # Migrations et seeds
│   ├── migrations/
│   └── seeds/
├── public/                  # Point d'entrée public
│   └── index.php
├── resources/               # Assets et vues
│   ├── views/
│   ├── css/
│   └── js/
├── routes/                  # Définition des routes
│   ├── web.php
│   └── api.php
├── src/                     # Code source du framework
│   └── Nexa/
│       ├── Core/
│       ├── Database/
│       ├── Routing/
│       ├── Auth/
│       ├── Events/
│       └── Queue/
├── storage/                  # Stockage (logs, cache, uploads)
│   ├── logs/
│   ├── cache/
│   └── uploads/
├── examples/                 # Exemples d'utilisation
│   └── complete_app.php
├── docs/                     # Documentation
│   └── PHASE2.md
├── nexa                      # CLI exécutable
├── NexaCLI.php              # Classe CLI principale
└── README.md                # Ce fichier
```

## 🔧 Configuration Avancée

### Configuration de la Base de Données

```php
// config/database.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'nexa',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
        ],
    ],
];
```

### Configuration des Événements

```php
// config/events.php
return [
    'listeners' => [
        'Nexa\Events\UserRegistered' => [
            'App\Listeners\SendWelcomeEmail',
            'App\Listeners\CreateUserProfile',
        ],
        'Nexa\Events\UserLoggedIn' => [
            'App\Listeners\LogUserActivity',
        ],
    ],
];
```

### Configuration des Queues

```php
// config/queue.php
return [
    'default' => 'database',
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'retry_after' => 90,
        ],
    ],
];
```

## 📊 Performance

Nexa Framework est conçu pour être rapide et efficace :

- **Temps de réponse** : ~5ms pour les routes simples
- **Empreinte mémoire** : ~2MB sans ORM, ~10MB avec ORM complet
- **Requêtes par seconde** : ~1000 req/s sur un serveur modeste

<<<<<<< HEAD
## 🚀 Avantages de Nexa Framework

### 🎯 Productivité Améliorée
- **Développement rapide** : Outils CLI pour générer du code rapidement
- **Auto-découverte** : Détection automatique des composants
- **Templates .nx** : Système de templates moderne et flexible
- **Validation intégrée** : Système de validation robuste et extensible

### ⚡ Performance Optimisée
- **Routage efficace** : Système de routage rapide et optimisé
- **Cache intelligent** : Mise en cache automatique des éléments coûteux
- **Compilation optimisée** : Templates compilés pour de meilleures performances
- **Architecture légère** : Framework conçu pour être rapide et efficace

### 🔒 Sécurité Robuste
- **Protection CSRF** : Protection automatique contre les attaques CSRF
- **Validation des données** : Nettoyage et validation automatique des entrées
- **Authentification sécurisée** : Système d'authentification flexible
- **Chiffrement intégré** : Outils de chiffrement pour protéger les données

### 🌐 Écosystème Moderne
- **Documentation claire** : Documentation complète et bien structurée
- **Outils de développement** : CLI et outils pour faciliter le développement
- **Architecture modulaire** : Code organisé et maintenable
- **Tests intégrés** : Framework de tests pour assurer la qualité

## 🗺️ Roadmap de Développement

### Version Actuelle : 3.0 ✅
- ✅ Architecture moderne et claire
- ✅ Auto-découverte des composants
- ✅ Templates .nx fonctionnels
- ✅ CLI avec commandes utiles

### Version 3.1 : Améliorations 🚧
- 🔄 Amélioration des performances
- 🔄 Outils de développement avancés
- 🔄 Documentation enrichie
- 🔄 Tests automatisés étendus

### Version 4.0 : Fonctionnalités Avancées 🔮
- 🔮 Support WebSockets natif
- 🔮 API GraphQL intégrée
- 🔮 Système de plugins
- 🔮 Interface d'administration

## 📚 Documentation

- 🏗️ [Architecture](docs/ARCHITECTURE.md) - Structure du framework
- 📖 [Guide de Démarrage](docs/GETTING_STARTED.md) - Premiers pas avec Nexa
- ⚡ [Performance](docs/PERFORMANCE.md) - Optimisation et bonnes pratiques
- 🎨 [Templates .nx](docs/NX_TEMPLATES.md) - Système de templates
- 🔒 [Sécurité](docs/SECURITY.md) - Guide de sécurité
- 🛠️ [CLI](docs/CLI.md) - Interface en ligne de commande

## 🛠️ Extensions VSCode

Nexa Framework propose une suite complète d'extensions VSCode pour une expérience de développement optimale :

### 🎨 Extensions Principales

#### 1. **Nexa .nx Template Support**
- **Description** : Support complet des fichiers `.nx` avec coloration syntaxique et IntelliSense
- **Fonctionnalités** :
  - Coloration syntaxique avancée pour les templates `.nx`
  - Autocomplétion intelligente des directives Nexa
  - Snippets de code pour composants et structures
  - Prévisualisation en temps réel
  - Navigation et hover informatif
- **Installation** : `ext install nexa.nx-template-support`

#### 2. **Nexa Code Snippets Pro**
- **Description** : Générateur intelligent de snippets de code pour Nexa
- **Fonctionnalités** :
  - Génération automatique de handlers, entités, middleware
  - Snippets contextuels basés sur le projet
  - Support WebSocket, GraphQL, et microservices
  - Templates de tests et validation
- **Installation** : `ext install nexa.code-snippets-pro`

#### 3. **Nexa Project Generator**
- **Description** : Générateur de projets et scaffolding intelligent
- **Fonctionnalités** :
  - Création de nouveaux projets Nexa
  - Scaffolding de projets existants
  - Génération d'APIs, CRUD, et microservices
  - Configuration Docker et CI/CD
  - Gestion des templates de projet
- **Installation** : `ext install nexa.project-generator`

### 🔧 Extensions Spécialisées

#### 4. **Nexa Security Scanner**
- **Description** : Scanner de sécurité intégré pour code Nexa
- **Fonctionnalités** :
  - Détection automatique des vulnérabilités
  - Vérification de conformité sécuritaire
  - Audit des dépendances
  - Suggestions de corrections automatiques
- **Installation** : `ext install nexa.security-scanner`

#### 5. **Nexa Test Runner**
- **Description** : Exécuteur de tests intégré avec couverture
- **Fonctionnalités** :
  - Exécution de tests PHPUnit et Pest
  - Analyse de couverture de code
  - Génération automatique de tests
  - Rapports détaillés et exports
- **Installation** : `ext install nexa.test-runner`

#### 6. **Nexa Performance Monitor**
- **Description** : Monitoring des performances en temps réel
- **Fonctionnalités** :
  - Analyse des performances du code
  - Détection des goulots d'étranglement
  - Suggestions d'optimisation
  - Rapports de performance détaillés
- **Installation** : `ext install nexa.performance-monitor`

### 🎯 Extensions Avancées

#### 7. **Nexa API Tester**
- **Description** : Testeur d'API intégré avec interface graphique
- **Fonctionnalités** :
  - Interface de test d'API intuitive
  - Gestion des collections de requêtes
  - Export vers Postman
  - Tests automatisés d'API
- **Installation** : `ext install nexa.api-tester`

#### 8. **Nexa Database Manager**
- **Description** : Gestionnaire de base de données visuel
- **Fonctionnalités** :
  - Explorateur de base de données
  - Éditeur de migrations visuelles
  - Visualiseur d'entités et relations
  - Prévisualisation de schémas
- **Installation** : `ext install nexa.database-manager`

#### 9. **Nexa GraphQL Studio**
- **Description** : Studio GraphQL complet pour Nexa
- **Fonctionnalités** :
  - Éditeur de schémas GraphQL
  - Testeur de requêtes intégré
  - Générateur de resolvers
  - Documentation automatique
- **Installation** : `ext install nexa.graphql-studio`

#### 10. **Nexa Component Library**
- **Description** : Bibliothèque de composants `.nx` avec prévisualisation
- **Fonctionnalités** :
  - Galerie de composants prêts à l'emploi
  - Prévisualisation en temps réel
  - Insertion directe dans l'éditeur
  - Gestion des catégories de composants
- **Installation** : `ext install nexa.component-library`

#### 11. **Nexa Theme Designer**
- **Description** : Concepteur de thèmes visuels pour Nexa
- **Fonctionnalités** :
  - Création et édition de thèmes
  - Palettes de couleurs intelligentes
  - Prévisualisation en temps réel
  - Export et partage de thèmes
- **Installation** : `ext install nexa.theme-designer`

#### 12. **Nexa CLI Tools**
- **Description** : Interface graphique pour les commandes CLI Nexa
- **Fonctionnalités** :
  - Exécution de commandes via interface graphique
  - Historique des commandes
  - Templates de commandes personnalisées
  - Intégration terminal avancée
- **Installation** : `ext install nexa.cli-tools`

### 📦 Pack d'Extensions

#### **Nexa Development Suite**
Installez toutes les extensions en une fois :
```bash
code --install-extension nexa.development-suite
```

### ⚙️ Configuration Recommandée

```json
{
  "nexa.autoCompletion.enabled": true,
  "nexa.preview.autoRefresh": true,
  "nexa.validation.enabled": true,
  "nexa.formatting.enabled": true,
  "nexa.security.autoScan": true,
  "nexa.performance.monitoring": true,
  "files.associations": {
    "*.nx": "nx"
  }
}
```

## 🤝 Contribution

Nous accueillons chaleureusement les contributions ! Voici comment vous pouvez aider :

### Signaler des bugs

1. Vérifiez que le bug n'a pas déjà été signalé
2. Créez une issue détaillée avec :
   - Description du problème
   - Étapes pour reproduire
   - Environnement (PHP, OS, etc.)
   - Code d'exemple si possible

### Proposer des fonctionnalités

1. Ouvrez une issue pour discuter de votre idée
2. Attendez les retours de la communauté
3. Implémentez la fonctionnalité
4. Soumettez une pull request

### Développement

```bash
# Fork et clone
git clone https://github.com/votre-username/nexa-framework.git
cd nexa-framework

# Installation des dépendances
composer install

# Tests
php vendor/bin/phpunit

# Standards de code
php vendor/bin/php-cs-fixer fix
```

### Guidelines

- **Code style** : PSR-12
- **Tests** : Couverture minimale de 80%
- **Documentation** : Commentaires PHPDoc
- **Commits** : Messages clairs et descriptifs
- **Branches** : `feature/nom-fonctionnalite` ou `fix/nom-bug`

## 📈 Roadmap

### Version 3.1 (Q2 2024)
- [ ] Support des WebSockets
- [ ] Queue system avancé
- [ ] API GraphQL intégrée
- [ ] Hot-reload pour les assets
- [ ] Amélioration des performances

### Version 3.2 (Q3 2024)
- [ ] Support multi-tenant
- [ ] Système de plugins avancé
- [ ] Interface d'administration
- [ ] Monitoring intégré
- [ ] Support Docker officiel

### Version 4.0 (Q4 2024)
- [ ] Architecture microservices
- [ ] Support PHP 8.3+
- [ ] Refactoring complet du core
- [ ] Nouvelle CLI interactive
- [ ] Performance x2

## 🏆 Communauté

- **Discord** : [Rejoindre le serveur](https://discord.gg/nexa)
- **Forum** : [forum.nexa-framework.com](https://forum.nexa-framework.com)
- **Twitter** : [@NexaFramework](https://twitter.com/NexaFramework)
- **Blog** : [blog.nexa-framework.com](https://blog.nexa-framework.com)

## 📚 Ressources

- **Documentation complète** : [docs.nexa-framework.com](https://docs.nexa-framework.com)
- **Tutoriels vidéo** : [YouTube](https://youtube.com/NexaFramework)
- **Exemples de projets** : [github.com/nexa/examples](https://github.com/nexa/examples)
- **Packages officiels** : [packagist.org/packages/nexa](https://packagist.org/packages/nexa/)

## 🎯 Sponsors

Nexa Framework est rendu possible grâce au soutien de nos sponsors :

- **🥇 Sponsors Or** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)
- **🥈 Sponsors Argent** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)
- **🥉 Sponsors Bronze** : [Votre entreprise ici](mailto:sponsors@nexa-framework.com)

[Devenir sponsor](https://github.com/sponsors/nexa-framework)

## 📄 Licence

Nexa Framework est un logiciel open source sous licence [MIT](LICENSE).

```
MIT License

Copyright (c) 2024 Nexa Framework

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

<div align="center">

**Fait avec ❤️ par l'équipe Nexa Framework**

[Site web](https://nexa-framework.com) • [Documentation](https://docs.nexa-framework.com) • [GitHub](https://github.com/nexa/framework) • [Discord](https://discord.gg/nexa)

⭐ **N'oubliez pas de donner une étoile si Nexa vous plaît !** ⭐

</div>
=======
## 📚 Documentation

### Guides Essentiels
- [🚀 Guide de Démarrage Rapide](docs/QUICK_START.md) - Commencez en 5 minutes
- [📖 Documentation API Complète](docs/API_DOCUMENTATION.md) - Référence technique
- [✨ Meilleures Pratiques](docs/BEST_PRACTICES.md) - Patterns et anti-patterns
- [🎓 Tutoriels Détaillés](docs/TUTORIALS.md) - Apprenez par l'exemple

### Développement
- [🤝 Guide de Contribution](CONTRIBUTING.md) - Comment contribuer
- [📝 Changelog](CHANGELOG.md) - Historique des versions
- [🗺️ Phase 1 - Améliorations](PHASE1_IMPROVEMENTS.md)
- [🗺️ Phase 2 - Roadmap](PHASE2_ROADMAP.md)
- [🗺️ Phase 3 - Roadmap](PHASE3_ROADMAP.md)

### Ressources
- [🧪 Tests](tests/) - Suite de tests complète
- [💡 Exemples](examples/) - Projets d'exemple
- [🔧 Outils](tools/) - Utilitaires de développement

## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez notre [guide de contribution](CONTRIBUTING.md) pour plus d'informations.

## 📄 Licence

Nexa Framework est un logiciel open-source sous licence [MIT](LICENSE).
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77
