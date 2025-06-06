# Nexa Framework

Nexa est un framework PHP moderne, léger et puissant pour le développement d'applications web et d'APIs.

![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)
![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🚀 Aperçu

Nexa Framework est conçu pour offrir une expérience de développement fluide tout en maintenant des performances exceptionnelles. Il combine la simplicité d'utilisation avec des fonctionnalités avancées pour répondre aux besoins des applications modernes.

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