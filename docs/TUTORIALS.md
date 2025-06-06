# Tutoriels Pas-à-Pas - Framework Nexa

## Table des Matières

### Pour Débutants
1. [Première Application avec Nexa](#première-application-avec-nexa)
2. [Création d'un Blog Simple](#création-dun-blog-simple)
3. [Système d'Authentification Basique](#système-dauthentification-basique)
4. [API REST Simple](#api-rest-simple)

### Pour Développeurs Expérimentés
5. [Application E-commerce Complète](#application-e-commerce-complète)
6. [API GraphQL Avancée](#api-graphql-avancée)
7. [Microservices avec Nexa](#microservices-avec-nexa)
8. [Application Temps Réel avec WebSockets](#application-temps-réel-avec-websockets)
9. [Système de Plugins Personnalisés](#système-de-plugins-personnalisés)

---

## Première Application avec Nexa

### Objectif
Créer une application "Hello World" et comprendre les bases du framework.

### Étape 1 : Installation

```bash
# Créer un nouveau projet
composer create-project nexa/framework ma-premiere-app
cd ma-premiere-app

# Démarrer le serveur de développement
php -S localhost:8000 -t public
```

### Étape 2 : Première Route

Ouvrez `routes/web.php` et ajoutez :

```php
<?php
use Nexa\Routing\Router;

// Route d'accueil
Router::get('/', function() {
    return view('welcome');
});

// Votre première route personnalisée
Router::get('/hello', function() {
    return 'Hello, Nexa Framework!';
});

// Route avec paramètre
Router::get('/hello/{name}', function($name) {
    return "Hello, {$name}!";
});
```

### Étape 3 : Première Vue

Créez `resources/views/hello.nx` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello Nexa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-center text-blue-600 mb-4">
                Bienvenue dans Nexa!
            </h1>
            <p class="text-gray-600 text-center">
                Votre première application avec le framework Nexa.
            </p>
            <div class="mt-6 text-center">
                <a href="/hello/Développeur" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Dire Bonjour
                </a>
            </div>
        </div>
    </div>
</body>
</html>
```

### Étape 4 : Premier Contrôleur

Créez `app/Http/Controllers/HelloController.php` :

```php
<?php
namespace App\Http\Controllers;

use Nexa\Http\Controller;
use Nexa\Http\Request;

class HelloController extends Controller
{
    public function index()
    {
        return view('hello');
    }
    
    public function show($name)
    {
        $message = "Bonjour, {$name}! Bienvenue dans Nexa Framework.";
        return view('hello', compact('message'));
    }
    
    public function form()
    {
        return view('hello-form');
    }
    
    public function submit(Request $request)
    {
        $name = $request->input('name', 'Anonyme');
        return redirect("/hello/{$name}");
    }
}
```

Mettez à jour `routes/web.php` :

```php
// Routes avec contrôleur
Router::get('/hello', 'HelloController@index');
Router::get('/hello/{name}', 'HelloController@show');
Router::get('/hello-form', 'HelloController@form');
Router::post('/hello-form', 'HelloController@submit');
```

### Étape 5 : Formulaire Interactif

Créez `resources/views/hello-form.nx` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire Hello</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">
                Dites Bonjour!
            </h1>
            
            <form method="POST" action="/hello-form" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Votre nom :
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Envoyer
                </button>
            </form>
            
            <div class="mt-4 text-center">
                <a href="/hello" class="text-blue-500 hover:text-blue-700">
                    ← Retour
                </a>
            </div>
        </div>
    </div>
</body>
</html>
```

### Résultat
Vous avez maintenant une application fonctionnelle avec :
- Routes personnalisées
- Contrôleurs
- Vues avec Tailwind CSS
- Formulaires
- Navigation

---

## Création d'un Blog Simple

### Objectif
Créer un blog avec articles, commentaires et administration.

### Étape 1 : Configuration de la Base de Données

Configurez `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexa_blog
DB_USERNAME=root
DB_PASSWORD=
```

### Étape 2 : Migrations

Créez les migrations :

```bash
# Migration pour les articles
php nexa make:migration create_posts_table

# Migration pour les commentaires
php nexa make:migration create_comments_table
```

`database/migrations/create_posts_table.php` :

```php
<?php
use Nexa\Database\Migration;
use Nexa\Database\Blueprint;
use Nexa\Database\Schema;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('posts');
    }
}
```

`database/migrations/create_comments_table.php` :

```php
<?php
use Nexa\Database\Migration;
use Nexa\Database\Blueprint;
use Nexa\Database\Schema;

class CreateCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('author_name');
            $table->string('author_email');
            $table->text('content');
            $table->boolean('approved')->default(false);
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
```

Exécutez les migrations :

```bash
php nexa migrate
```

### Étape 3 : Modèles

`app/Models/Post.php` :

```php
<?php
namespace App\Models;

use Nexa\Database\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'published', 'published_at'
    ];
    
    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime'
    ];
    
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    public function approvedComments()
    {
        return $this->hasMany(Comment::class)->where('approved', true);
    }
    
    public function scopePublished($query)
    {
        return $query->where('published', true)
                    ->where('published_at', '<=', now());
    }
    
    public function getRouteKeyName()
    {
        return 'slug';
    }
    
    // Génération automatique du slug
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = static::generateSlug($post->title);
            }
        });
    }
    
    private static function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        
        return $count ? "{$slug}-{$count}" : $slug;
    }
}
```

`app/Models/Comment.php` :

```php
<?php
namespace App\Models;

use Nexa\Database\Model;

class Comment extends Model
{
    protected $fillable = [
        'post_id', 'author_name', 'author_email', 'content', 'approved'
    ];
    
    protected $casts = [
        'approved' => 'boolean'
    ];
    
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
    
    public function scopeApproved($query)
    {
        return $query->where('approved', true);
    }
}
```

### Étape 4 : Contrôleurs

`app/Http/Controllers/BlogController.php` :

```php
<?php
namespace App\Http\Controllers;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Nexa\Validation\ValidatesRequests;

class BlogController extends Controller
{
    use ValidatesRequests;
    
    public function index()
    {
        $posts = Post::published()
                    ->orderBy('published_at', 'desc')
                    ->paginate(10);
                    
        return view('blog.index', compact('posts'));
    }
    
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
                   ->published()
                   ->with('approvedComments')
                   ->firstOrFail();
                   
        return view('blog.show', compact('post'));
    }
    
    public function storeComment(Request $request, $slug)
    {
        $post = Post::where('slug', $slug)->firstOrFail();
        
        $validatedData = $this->validate($request, [
            'author_name' => 'required|min:2|max:100',
            'author_email' => 'required|email',
            'content' => 'required|min:10|max:1000'
        ]);
        
        $comment = new Comment($validatedData);
        $comment->post_id = $post->id;
        $comment->save();
        
        return redirect("/blog/{$slug}")
               ->with('success', 'Votre commentaire a été soumis et sera modéré.');
    }
}
```

### Étape 5 : Routes

Mettez à jour `routes/web.php` :

```php
// Routes du blog
Router::get('/blog', 'BlogController@index');
Router::get('/blog/{slug}', 'BlogController@show');
Router::post('/blog/{slug}/comments', 'BlogController@storeComment');
```

### Étape 6 : Vues

`resources/views/blog/index.nx` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Nexa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <header class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">Blog Nexa</h1>
            <p class="text-gray-600">Découvrez nos derniers articles</p>
        </header>
        
        <div class="max-w-4xl mx-auto">
            <?php foreach ($posts as $post): ?>
            <article class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-3">
                    <a href="/blog/<?= $post->slug ?>" class="hover:text-blue-600">
                        <?= htmlspecialchars($post->title) ?>
                    </a>
                </h2>
                
                <div class="text-gray-500 text-sm mb-4">
                    Publié le <?= $post->published_at->format('d/m/Y') ?>
                </div>
                
                <div class="text-gray-700 mb-4">
                    <?= $post->excerpt ?: substr(strip_tags($post->content), 0, 200) . '...' ?>
                </div>
                
                <a href="/blog/<?= $post->slug ?>" 
                   class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Lire la suite
                </a>
            </article>
            <?php endforeach; ?>
            
            <?php if (empty($posts)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">Aucun article publié pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
```

`resources/views/blog/show.nx` :

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post->title) ?> - Blog Nexa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Navigation -->
            <nav class="mb-8">
                <a href="/blog" class="text-blue-500 hover:text-blue-700">
                    ← Retour au blog
                </a>
            </nav>
            
            <!-- Article -->
            <article class="bg-white rounded-lg shadow-md p-8 mb-8">
                <header class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">
                        <?= htmlspecialchars($post->title) ?>
                    </h1>
                    <div class="text-gray-500">
                        Publié le <?= $post->published_at->format('d/m/Y à H:i') ?>
                    </div>
                </header>
                
                <div class="prose max-w-none">
                    <?= nl2br(htmlspecialchars($post->content)) ?>
                </div>
            </article>
            
            <!-- Commentaires -->
            <section class="bg-white rounded-lg shadow-md p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">
                    Commentaires (<?= count($post->approvedComments) ?>)
                </h3>
                
                <!-- Liste des commentaires -->
                <?php foreach ($post->approvedComments as $comment): ?>
                <div class="border-b border-gray-200 pb-4 mb-4 last:border-b-0">
                    <div class="flex justify-between items-start mb-2">
                        <strong class="text-gray-800">
                            <?= htmlspecialchars($comment->author_name) ?>
                        </strong>
                        <span class="text-gray-500 text-sm">
                            <?= $comment->created_at->format('d/m/Y à H:i') ?>
                        </span>
                    </div>
                    <p class="text-gray-700">
                        <?= nl2br(htmlspecialchars($comment->content)) ?>
                    </p>
                </div>
                <?php endforeach; ?>
                
                <!-- Formulaire de commentaire -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <h4 class="text-xl font-bold text-gray-800 mb-4">
                        Laisser un commentaire
                    </h4>
                    
                    <form method="POST" action="/blog/<?= $post->slug ?>/comments" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="author_name" class="block text-sm font-medium text-gray-700">
                                    Nom *
                                </label>
                                <input type="text" 
                                       id="author_name" 
                                       name="author_name" 
                                       required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="author_email" class="block text-sm font-medium text-gray-700">
                                    Email *
                                </label>
                                <input type="email" 
                                       id="author_email" 
                                       name="author_email" 
                                       required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">
                                Commentaire *
                            </label>
                            <textarea id="content" 
                                      name="content" 
                                      rows="4" 
                                      required
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Publier le commentaire
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
```

### Étape 7 : Seeding (Données de Test)

Créez `database/seeders/BlogSeeder.php` :

```php
<?php
use App\Models\Post;
use App\Models\Comment;

class BlogSeeder
{
    public function run()
    {
        // Créer des articles de test
        $posts = [
            [
                'title' => 'Bienvenue sur notre blog',
                'content' => 'Ceci est notre premier article de blog. Nous sommes ravis de partager nos connaissances avec vous.',
                'excerpt' => 'Premier article de notre blog.',
                'published' => true,
                'published_at' => date('Y-m-d H:i:s')
            ],
            [
                'title' => 'Les avantages du framework Nexa',
                'content' => 'Nexa Framework offre de nombreux avantages pour le développement web moderne...',
                'excerpt' => 'Découvrez pourquoi choisir Nexa.',
                'published' => true,
                'published_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
        
        foreach ($posts as $postData) {
            $post = Post::create($postData);
            
            // Ajouter des commentaires de test
            Comment::create([
                'post_id' => $post->id,
                'author_name' => 'Jean Dupont',
                'author_email' => 'jean@example.com',
                'content' => 'Excellent article, merci pour le partage!',
                'approved' => true
            ]);
        }
    }
}
```

### Résultat
Vous avez maintenant un blog fonctionnel avec :
- Liste des articles
- Affichage détaillé des articles
- Système de commentaires
- Base de données structurée
- Interface utilisateur moderne

---

*Les tutoriels continuent avec les sections suivantes : Système d'Authentification Basique, API REST Simple, Application E-commerce Complète, API GraphQL Avancée, Microservices, WebSockets, et Système de Plugins.*