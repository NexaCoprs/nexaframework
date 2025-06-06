# Guide de Démarrage Rapide - Framework Nexa

## Installation en 5 Minutes

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Serveur web (Apache/Nginx) ou PHP built-in server
- Base de données (MySQL, PostgreSQL, SQLite)

### 1. Installation

```bash
# Cloner le projet
git clone https://github.com/votre-username/nexa-framework.git mon-projet
cd mon-projet

# Installer les dépendances
composer install

# Copier le fichier de configuration
cp .env.example .env

# Générer la clé d'application
php nexa key:generate
```

### 2. Configuration de Base

Éditez le fichier `.env` :

```env
# Application
APP_NAME="Mon Application Nexa"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexa_app
DB_USERNAME=root
DB_PASSWORD=

# JWT (pour l'authentification)
JWT_SECRET=votre-secret-jwt-ici
JWT_TTL=3600
```

### 3. Démarrage du Serveur

```bash
# Démarrer le serveur de développement
php -S localhost:8000 -t public
```

Ouvrez votre navigateur sur `http://localhost:8000` 🎉

---

## Votre Première Application en 10 Minutes

### Étape 1 : Créer un Modèle

```php
<?php
// app/Models/Article.php

use Nexa\Database\Model;

class Article extends Model
{
    protected string $table = 'articles';
    
    protected array $fillable = [
        'title', 'content', 'author_id', 'published_at'
    ];
    
    protected array $casts = [
        'published_at' => 'datetime'
    ];
    
    // Relations
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    // Méthodes métier
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }
}
```

### Étape 2 : Créer une Migration

```php
<?php
// database/migrations/2025_01_01_000001_create_articles_table.php

use Nexa\Database\Migration;
use Nexa\Database\Schema\Blueprint;
use Nexa\Database\Schema\Schema;

class CreateArticlesTable extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->foreignId('author_id')->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->index(['published_at', 'created_at']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
}
```

### Étape 3 : Exécuter la Migration

```bash
php nexa migrate
```

### Étape 4 : Créer un Contrôleur

```php
<?php
// app/Http/Controllers/ArticleController.php

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class ArticleController extends Controller
{
    // Afficher tous les articles
    public function index(): Response
    {
        $articles = Article::with('author')
                          ->where('published_at', '<=', now())
                          ->orderBy('published_at', 'desc')
                          ->paginate(10);
        
        return view('articles.index', compact('articles'));
    }
    
    // Afficher un article
    public function show(int $id): Response
    {
        $article = Article::with('author')->findOrFail($id);
        
        if (!$article->isPublished()) {
            abort(404);
        }
        
        return view('articles.show', compact('article'));
    }
    
    // Formulaire de création
    public function create(): Response
    {
        return view('articles.create');
    }
    
    // Sauvegarder un nouvel article
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'nullable|date'
        ]);
        
        $article = Article::create([
            ...$validated,
            'author_id' => auth()->id()
        ]);
        
        return redirect('/articles/' . $article->id)
               ->with('success', 'Article créé avec succès!');
    }
}
```

### Étape 5 : Définir les Routes

```php
<?php
// routes/web.php

use Nexa\Routing\Router;

// Page d'accueil
Router::get('/', function() {
    return view('welcome');
});

// Routes pour les articles
Router::get('/articles', 'ArticleController@index');
Router::get('/articles/create', 'ArticleController@create')->middleware('auth');
Router::post('/articles', 'ArticleController@store')->middleware('auth');
Router::get('/articles/{id}', 'ArticleController@show');

// Routes d'authentification
Router::group(['prefix' => 'auth'], function() {
    Router::get('/login', 'AuthController@showLogin');
    Router::post('/login', 'AuthController@login');
    Router::post('/logout', 'AuthController@logout');
    Router::get('/register', 'AuthController@showRegister');
    Router::post('/register', 'AuthController@register');
});
```

### Étape 6 : Créer les Vues

**Layout principal** (`views/layouts/app.nx`) :
```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Mon Blog' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-gray-800">Mon Blog</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/articles" class="text-gray-600 hover:text-gray-900">Articles</a>
                    @if(auth()->check())
                        <a href="/articles/create" class="bg-blue-500 text-white px-4 py-2 rounded">Écrire</a>
                        <form action="/auth/logout" method="POST" class="inline">
                            <button type="submit" class="text-gray-600 hover:text-gray-900">Déconnexion</button>
                        </form>
                    @else
                        <a href="/auth/login" class="text-gray-600 hover:text-gray-900">Connexion</a>
                        <a href="/auth/register" class="bg-green-500 text-white px-4 py-2 rounded">Inscription</a>
                    @endif
                </div>
            </div>
        </div>
    </nav>
    
    <main class="max-w-7xl mx-auto py-6 px-4">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif
        
        {{ $content }}
    </main>
</body>
</html>
```

**Liste des articles** (`views/articles/index.nx`) :
```html
@extends('layouts.app')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Articles</h1>
    @if(auth()->check())
        <a href="/articles/create" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Nouvel Article
        </a>
    @endif
</div>

<div class="grid gap-6">
    @foreach($articles as $article)
        <article class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-2">
                <a href="/articles/{{ $article->id }}" class="text-blue-600 hover:text-blue-800">
                    {{ $article->title }}
                </a>
            </h2>
            
            <div class="text-gray-600 text-sm mb-3">
                Par {{ $article->author->name }} • 
                {{ $article->published_at->format('d/m/Y à H:i') }}
            </div>
            
            <p class="text-gray-700 leading-relaxed">
                {{ substr($article->content, 0, 200) }}...
            </p>
            
            <a href="/articles/{{ $article->id }}" class="inline-block mt-3 text-blue-600 hover:text-blue-800">
                Lire la suite →
            </a>
        </article>
    @endforeach
</div>

<!-- Pagination -->
@if($articles->hasPages())
    <div class="mt-8 flex justify-center">
        {{ $articles->links() }}
    </div>
@endif
@endsection
```

---

## API REST en 5 Minutes

### 1. Créer un Contrôleur API

```php
<?php
// app/Http/Controllers/Api/ArticleController.php

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::with('author')
                          ->where('published_at', '<=', now())
                          ->orderBy('published_at', 'desc')
                          ->paginate(15);
        
        return response()->json($articles);
    }
    
    public function show(int $id): Response
    {
        $article = Article::with('author')->findOrFail($id);
        
        return response()->json($article);
    }
    
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'published_at' => 'nullable|date'
        ]);
        
        $article = Article::create([
            ...$validated,
            'author_id' => auth()->id()
        ]);
        
        return response()->json($article, 201);
    }
    
    public function update(Request $request, int $id): Response
    {
        $article = Article::findOrFail($id);
        
        // Vérifier les permissions
        if ($article->author_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'published_at' => 'sometimes|nullable|date'
        ]);
        
        $article->update($validated);
        
        return response()->json($article);
    }
    
    public function destroy(int $id): Response
    {
        $article = Article::findOrFail($id);
        
        if ($article->author_id !== auth()->id()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        
        $article->delete();
        
        return response()->json(['message' => 'Article supprimé']);
    }
}
```

### 2. Routes API

```php
<?php
// routes/api.php

use Nexa\Routing\Router;

// Routes publiques
Router::group(['prefix' => 'api/v1'], function() {
    Router::get('/articles', 'Api\ArticleController@index');
    Router::get('/articles/{id}', 'Api\ArticleController@show');
});

// Routes protégées par JWT
Router::group(['prefix' => 'api/v1', 'middleware' => 'jwt'], function() {
    Router::post('/articles', 'Api\ArticleController@store');
    Router::put('/articles/{id}', 'Api\ArticleController@update');
    Router::delete('/articles/{id}', 'Api\ArticleController@destroy');
});
```

### 3. Test de l'API

```bash
# Obtenir tous les articles
curl http://localhost:8000/api/v1/articles

# Obtenir un article spécifique
curl http://localhost:8000/api/v1/articles/1

# Créer un article (avec authentification)
curl -X POST http://localhost:8000/api/v1/articles \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "title": "Mon Premier Article",
    "content": "Contenu de l'article...",
    "published_at": "2025-01-15 10:00:00"
  }'
```

---

## Authentification JWT

### 1. Configuration

Dans `.env` :
```env
JWT_SECRET=votre-secret-super-securise-ici
JWT_TTL=3600  # 1 heure
JWT_REFRESH_TTL=20160  # 2 semaines
```

### 2. Contrôleur d'Authentification

```php
<?php
// app/Http/Controllers/Api/AuthController.php

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Auth\JWT;

class AuthController extends Controller
{
    public function login(Request $request): Response
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }
        
        $token = JWT::encode(['user_id' => $user->id]);
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'expires_in' => config('jwt.ttl')
        ]);
    }
    
    public function register(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_DEFAULT)
        ]);
        
        $token = JWT::encode(['user_id' => $user->id]);
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'expires_in' => config('jwt.ttl')
        ], 201);
    }
    
    public function me(Request $request): Response
    {
        return response()->json(auth()->user());
    }
    
    public function refresh(Request $request): Response
    {
        $newToken = JWT::refresh($request->bearerToken());
        
        return response()->json([
            'token' => $newToken,
            'expires_in' => config('jwt.ttl')
        ]);
    }
}
```

### 3. Routes d'Authentification

```php
<?php
// routes/api.php

Router::group(['prefix' => 'api/v1/auth'], function() {
    Router::post('/login', 'Api\AuthController@login');
    Router::post('/register', 'Api\AuthController@register');
    
    Router::group(['middleware' => 'jwt'], function() {
        Router::get('/me', 'Api\AuthController@me');
        Router::post('/refresh', 'Api\AuthController@refresh');
    });
});
```

---

## Commandes Utiles

```bash
# Générer une clé d'application
php nexa key:generate

# Exécuter les migrations
php nexa migrate

# Rollback des migrations
php nexa migrate:rollback

# Créer une migration
php nexa make:migration create_posts_table

# Créer un modèle
php nexa make:model Post

# Créer un contrôleur
php nexa make:controller PostController

# Créer un middleware
php nexa make:middleware AuthMiddleware

# Vider le cache
php nexa cache:clear

# Lancer les tests
php nexa test

# Démarrer le serveur de développement
php nexa serve
```

---

## Prochaines Étapes

1. **Explorez la documentation complète** : [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)
2. **Suivez les tutoriels détaillés** : [TUTORIALS.md](./TUTORIALS.md)
3. **Appliquez les meilleures pratiques** : [BEST_PRACTICES.md](./BEST_PRACTICES.md)
4. **Consultez les exemples** dans le dossier `examples/`
5. **Rejoignez la communauté** sur GitHub

---

## Besoin d'Aide ?

- 📖 **Documentation** : Consultez les guides détaillés
- 🐛 **Bugs** : Ouvrez une issue sur GitHub
- 💬 **Questions** : Utilisez les discussions GitHub
- 📧 **Contact** : contact@nexa-framework.com

**Bon développement avec Nexa ! 🚀**