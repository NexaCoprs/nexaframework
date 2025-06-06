# Documentation API Complète - Framework Nexa

## Table des Matières

1. [Introduction](#introduction)
2. [Installation et Configuration](#installation-et-configuration)
3. [Architecture du Framework](#architecture-du-framework)
4. [Composants Core](#composants-core)
5. [Routage](#routage)
6. [Contrôleurs](#contrôleurs)
7. [Modèles et ORM](#modèles-et-orm)
8. [Authentification JWT](#authentification-jwt)
9. [Middleware](#middleware)
10. [Validation](#validation)
11. [Système d'Événements](#système-dévénements)
12. [Files d'Attente](#files-dattente)
13. [Cache](#cache)
14. [Logging](#logging)
15. [Tests](#tests)
16. [CLI](#cli)
17. [GraphQL](#graphql)
18. [WebSockets](#websockets)
19. [Microservices](#microservices)
20. [Plugins et Modules](#plugins-et-modules)

---

## Introduction

Nexa Framework est un framework PHP moderne, léger et puissant conçu pour le développement d'applications web et d'APIs. Il combine simplicité d'utilisation et fonctionnalités avancées pour répondre aux besoins des applications modernes.

### Caractéristiques Principales

- **Performance** : Architecture optimisée pour des temps de réponse rapides
- **Sécurité** : Protection CSRF, authentification JWT, validation robuste
- **Extensibilité** : Système de plugins et modules
- **Modernité** : Support PHP 8.2+, architecture événementielle
- **Simplicité** : API intuitive et documentation complète

---

## Installation et Configuration

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- Extension PDO pour la base de données

### Installation via Composer

```bash
composer create-project nexa/framework mon-projet
cd mon-projet
```

### Configuration de Base

#### Fichier .env

```env
APP_NAME="Mon Application Nexa"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexa_db
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=votre-clé-secrète-jwt
JWT_TTL=3600
```

#### Configuration des Services

Les fichiers de configuration se trouvent dans le dossier `config/` :

- `app.php` : Configuration générale de l'application
- `database.php` : Configuration de la base de données
- `cache.php` : Configuration du cache
- `logging.php` : Configuration des logs
- `phase2.php` : Configuration des fonctionnalités avancées

---

## Architecture du Framework

### Structure des Dossiers

```
nexa-project/
├── app/                    # Code de l'application
│   ├── Http/
│   │   └── Controllers/    # Contrôleurs
│   └── Models/            # Modèles
├── config/                # Fichiers de configuration
├── database/
│   └── migrations/        # Migrations de base de données
├── public/                # Point d'entrée web
├── resources/
│   └── views/             # Templates
├── routes/                # Définition des routes
├── src/Nexa/             # Code du framework
├── storage/              # Fichiers de stockage
└── tests/                # Tests automatisés
```

### Cycle de Vie d'une Requête

1. **Point d'entrée** : `public/index.php`
2. **Autoloader** : Chargement automatique des classes
3. **Application** : Initialisation de l'application Nexa
4. **Routage** : Résolution de la route
5. **Middleware** : Exécution des middlewares
6. **Contrôleur** : Traitement de la logique métier
7. **Réponse** : Génération et envoi de la réponse

---

## Composants Core

### Application

La classe `Nexa\Core\Application` est le cœur du framework.

```php
use Nexa\Core\Application;

$app = new Application();
$app->run();
```

#### Méthodes Principales

- `run()` : Lance l'application
- `bind($abstract, $concrete)` : Enregistre une liaison dans le conteneur
- `resolve($abstract)` : Résout une dépendance du conteneur

### Configuration

Accès à la configuration via la classe `Config` :

```php
use Nexa\Core\Config;

// Récupérer une valeur de configuration
$dbHost = Config::get('database.host');

// Définir une valeur de configuration
Config::set('app.timezone', 'Europe/Paris');

// Vérifier l'existence d'une clé
if (Config::has('app.debug')) {
    // ...
}
```

### Cache

Système de cache haute performance :

```php
use Nexa\Core\Cache;

// Stocker une valeur
Cache::put('user:123', $userData, 3600); // 1 heure

// Récupérer une valeur
$user = Cache::get('user:123');

// Vérifier l'existence
if (Cache::has('user:123')) {
    // ...
}

// Supprimer une valeur
Cache::forget('user:123');

// Vider tout le cache
Cache::flush();
```

### Logger

Système de logging compatible PSR-3 :

```php
use Nexa\Core\Logger;

// Différents niveaux de log
Logger::emergency('Système indisponible');
Logger::alert('Action immédiate requise');
Logger::critical('Erreur critique');
Logger::error('Erreur d\'exécution');
Logger::warning('Avertissement');
Logger::notice('Notice normale');
Logger::info('Information');
Logger::debug('Information de debug');

// Avec contexte
Logger::info('Utilisateur connecté', [
    'user_id' => 123,
    'ip' => '192.168.1.1'
]);
```

---

## Routage

### Définition des Routes

Les routes sont définies dans `routes/web.php` :

```php
use Nexa\Routing\Router;

// Route GET simple
Router::get('/', function() {
    return 'Bienvenue sur Nexa!';
});

// Route avec paramètre
Router::get('/users/{id}', function($id) {
    return "Utilisateur ID: {$id}";
});

// Route vers un contrôleur
Router::get('/profile', 'UserController@profile');

// Route POST
Router::post('/users', 'UserController@store');

// Routes avec contraintes
Router::get('/users/{id}', 'UserController@show')
    ->where('id', '[0-9]+');
```

### Groupes de Routes

```php
// Groupe avec préfixe
Router::group(['prefix' => 'api'], function() {
    Router::get('/users', 'ApiController@users');
    Router::get('/posts', 'ApiController@posts');
});

// Groupe avec middleware
Router::group(['middleware' => 'auth'], function() {
    Router::get('/dashboard', 'DashboardController@index');
    Router::get('/profile', 'UserController@profile');
});

// Groupe avec préfixe et middleware
Router::group([
    'prefix' => 'admin',
    'middleware' => ['auth', 'admin']
], function() {
    Router::get('/users', 'AdminController@users');
    Router::get('/settings', 'AdminController@settings');
});
```

### Méthodes HTTP Supportées

```php
Router::get($uri, $action);
Router::post($uri, $action);
Router::put($uri, $action);
Router::patch($uri, $action);
Router::delete($uri, $action);
Router::options($uri, $action);

// Route pour plusieurs méthodes
Router::match(['GET', 'POST'], '/contact', 'ContactController@handle');

// Route pour toutes les méthodes
Router::any('/webhook', 'WebhookController@handle');
```

### Génération d'URLs

```php
// URL vers une route nommée
Router::get('/users/{id}', 'UserController@show')->name('user.show');

// Génération de l'URL
$url = route('user.show', ['id' => 123]); // /users/123
```

---

## Contrôleurs

### Création d'un Contrôleur

```php
namespace App\Http\Controllers;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return view('users.index', compact('users'));
    }
    
    public function show($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        
        return view('users.show', compact('user'));
    }
    
    public function store(Request $request)
    {
        $validatedData = $this->validate($request, [
            'name' => 'required|min:2|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);
        
        $user = User::create($validatedData);
        
        return response()->json($user, 201);
    }
}
```

### Injection de Dépendances

```php
use App\Services\UserService;
use Nexa\Database\Model;

class UserController extends Controller
{
    private $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function index()
    {
        $users = $this->userService->getAllUsers();
        return view('users.index', compact('users'));
    }
}
```

### Réponses

```php
// Réponse JSON
return response()->json(['message' => 'Succès']);

// Réponse avec statut
return response()->json(['error' => 'Non trouvé'], 404);

// Réponse avec headers
return response()->json($data)
    ->header('X-Custom-Header', 'value')
    ->cookie('name', 'value', 3600);

// Redirection
return redirect('/dashboard');

// Redirection avec données
return redirect('/users')->with('success', 'Utilisateur créé');
```

---

## Modèles et ORM

### Définition d'un Modèle

```php
namespace App\Models;

use Nexa\Database\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    
    // Relations
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
```

### Opérations CRUD

```php
// Création
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => password_hash('secret', PASSWORD_DEFAULT)
]);

// Lecture
$user = User::find(1);
$users = User::all();
$activeUsers = User::where('is_active', true)->get();

// Mise à jour
$user = User::find(1);
$user->update(['name' => 'Jane Doe']);

// Suppression
$user = User::find(1);
$user->delete();

// Suppression multiple
User::where('is_active', false)->delete();
```

### Query Builder

```php
// Requêtes complexes
$users = User::where('age', '>', 18)
    ->where('city', 'Paris')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

// Jointures
$users = User::join('profiles', 'users.id', '=', 'profiles.user_id')
    ->select('users.*', 'profiles.bio')
    ->get();

// Agrégations
$count = User::count();
$avgAge = User::avg('age');
$maxAge = User::max('age');

// Requêtes avec relations
$users = User::with(['posts', 'profile'])->get();
```

### Relations

```php
// One-to-One
class User extends Model
{
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
}

// One-to-Many
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

// Many-to-Many
class User extends Model
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'user_tags');
    }
}

// Utilisation des relations
$user = User::find(1);
$profile = $user->profile;
$posts = $user->posts;
$tags = $user->tags;
```

---

## Authentification JWT

### Configuration JWT

Dans `config/phase2.php` :

```php
'jwt' => [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'algorithm' => 'HS256',
    'access_token_ttl' => 3600, // 1 heure
    'refresh_token_ttl' => 604800, // 7 jours
    'blacklist_enabled' => true,
    'blacklist_grace_period' => 300 // 5 minutes
]
```

### Utilisation du JWT Manager

```php
use Nexa\Auth\JWTManager;

$jwt = new JWTManager();

// Génération d'un token
$payload = ['user_id' => 123, 'role' => 'admin'];
$token = $jwt->generateToken($payload);

// Validation d'un token
try {
    $decodedPayload = $jwt->validateToken($token);
    echo "User ID: " . $decodedPayload['user_id'];
} catch (JWTException $e) {
    echo "Token invalide: " . $e->getMessage();
}

// Génération d'une paire de tokens
$tokens = $jwt->generateTokenPair($payload);
// $tokens['access_token']
// $tokens['refresh_token']

// Rafraîchissement d'un token
$newTokens = $jwt->refreshToken($refreshToken);

// Blacklist d'un token
$jwt->blacklistToken($token);
```

### Middleware JWT

```php
// Dans routes/web.php
Router::group(['middleware' => 'jwt'], function() {
    Router::get('/profile', 'UserController@profile');
    Router::post('/posts', 'PostController@store');
});
```

### Contrôleur d'Authentification

```php
use Nexa\Auth\JWTManager;
use Nexa\Http\Controller;
use Nexa\Http\Request;

class AuthController extends Controller
{
    private $jwt;
    
    public function __construct(JWTManager $jwt)
    {
        $this->jwt = $jwt;
    }
    
    public function login(Request $request)
    {
        $credentials = $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }
        
        $tokens = $this->jwt->generateTokenPair([
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        return response()->json([
            'user' => $user,
            'tokens' => $tokens
        ]);
    }
    
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        
        try {
            $newTokens = $this->jwt->refreshToken($refreshToken);
            return response()->json(['tokens' => $newTokens]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token de rafraîchissement invalide'], 401);
        }
    }
    
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $this->jwt->blacklistToken($token);
        }
        
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}
```

---

*Cette documentation continue avec les sections suivantes : Middleware, Validation, Système d'Événements, Files d'Attente, Tests, CLI, GraphQL, WebSockets, Microservices, et Plugins/Modules.*