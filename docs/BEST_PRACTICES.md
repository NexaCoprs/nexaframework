# Guide des Meilleures Pratiques - Framework Nexa

## Table des Matières

1. [Principes Généraux](#principes-généraux)
2. [Architecture et Structure](#architecture-et-structure)
3. [Patterns Recommandés](#patterns-recommandés)
4. [Anti-Patterns à Éviter](#anti-patterns-à-éviter)
5. [Sécurité](#sécurité)
6. [Performance](#performance)
7. [Tests](#tests)
8. [Maintenance et Évolutivité](#maintenance-et-évolutivité)
9. [Conventions de Code](#conventions-de-code)
10. [Gestion des Erreurs](#gestion-des-erreurs)

---

## Principes Généraux

### 1. SOLID Principles

#### Single Responsibility Principle (SRP)
✅ **Bon :**
```php
class UserValidator
{
    public function validate(array $data): array
    {
        // Logique de validation uniquement
        return $this->validateUserData($data);
    }
}

class UserRepository
{
    public function create(array $data): User
    {
        // Logique de persistance uniquement
        return User::create($data);
    }
}
```

❌ **Mauvais :**
```php
class UserController
{
    public function store(Request $request)
    {
        // Mélange validation, logique métier et persistance
        if (empty($request->input('email'))) {
            throw new Exception('Email requis');
        }
        
        $user = new User();
        $user->email = $request->input('email');
        $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
        $user->save();
        
        // Envoi d'email
        mail($user->email, 'Bienvenue', 'Merci de vous être inscrit');
        
        return response()->json($user);
    }
}
```

#### Open/Closed Principle (OCP)
✅ **Bon :**
```php
interface PaymentProcessorInterface
{
    public function process(Payment $payment): bool;
}

class StripeProcessor implements PaymentProcessorInterface
{
    public function process(Payment $payment): bool
    {
        // Logique Stripe
    }
}

class PayPalProcessor implements PaymentProcessorInterface
{
    public function process(Payment $payment): bool
    {
        // Logique PayPal
    }
}

class PaymentService
{
    public function __construct(private PaymentProcessorInterface $processor) {}
    
    public function processPayment(Payment $payment): bool
    {
        return $this->processor->process($payment);
    }
}
```

### 2. Dependency Injection

✅ **Bon :**
```php
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}
    
    public function store(Request $request)
    {
        $order = $this->orderService->create($request->validated());
        $this->paymentService->process($order);
        $this->notificationService->sendConfirmation($order);
        
        return response()->json($order, 201);
    }
}
```

❌ **Mauvais :**
```php
class OrderController extends Controller
{
    public function store(Request $request)
    {
        // Couplage fort avec des classes concrètes
        $orderService = new OrderService();
        $paymentService = new StripePaymentService();
        $notificationService = new EmailNotificationService();
        
        // ...
    }
}
```

---

## Architecture et Structure

### 1. Structure des Dossiers

✅ **Structure Recommandée :**
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/           # Contrôleurs API
│   │   ├── Admin/         # Contrôleurs admin
│   │   └── Web/           # Contrôleurs web
│   ├── Middleware/
│   ├── Requests/          # Form Requests
│   └── Resources/         # API Resources
├── Models/
├── Services/              # Logique métier
├── Repositories/          # Accès aux données
├── Events/
├── Listeners/
├── Jobs/
├── Exceptions/
└── Providers/
```

### 2. Séparation des Responsabilités

✅ **Architecture en Couches :**
```php
// Contrôleur - Interface utilisateur
class UserController extends Controller
{
    public function __construct(private UserService $userService) {}
    
    public function store(CreateUserRequest $request)
    {
        $user = $this->userService->createUser($request->validated());
        return new UserResource($user);
    }
}

// Service - Logique métier
class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationService $notificationService
    ) {}
    
    public function createUser(array $data): User
    {
        $user = $this->userRepository->create($data);
        $this->notificationService->sendWelcomeEmail($user);
        
        event(new UserRegistered($user));
        
        return $user;
    }
}

// Repository - Accès aux données
class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
```

---

## Patterns Recommandés

### 1. Repository Pattern

✅ **Implémentation :**
```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
    
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    // ...
}
```

### 2. Service Layer Pattern

✅ **Service avec logique métier :**
```php
class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InventoryService $inventoryService,
        private PaymentService $paymentService
    ) {}
    
    public function createOrder(array $items, User $user): Order
    {
        // Vérification du stock
        foreach ($items as $item) {
            if (!$this->inventoryService->isAvailable($item['product_id'], $item['quantity'])) {
                throw new InsufficientStockException();
            }
        }
        
        // Création de la commande
        $order = $this->orderRepository->create([
            'user_id' => $user->id,
            'items' => $items,
            'total' => $this->calculateTotal($items),
            'status' => OrderStatus::PENDING
        ]);
        
        // Réservation du stock
        $this->inventoryService->reserve($items);
        
        return $order;
    }
    
    private function calculateTotal(array $items): float
    {
        return array_sum(array_map(function ($item) {
            return $item['price'] * $item['quantity'];
        }, $items));
    }
}
```

### 3. Factory Pattern

✅ **Factory pour créer des objets complexes :**
```php
class PaymentProcessorFactory
{
    public function create(string $type): PaymentProcessorInterface
    {
        return match($type) {
            'stripe' => new StripeProcessor(config('payment.stripe')),
            'paypal' => new PayPalProcessor(config('payment.paypal')),
            'bank' => new BankTransferProcessor(config('payment.bank')),
            default => throw new InvalidArgumentException("Unknown payment type: {$type}")
        };
    }
}
```

### 4. Observer Pattern (Events)

✅ **Utilisation des événements :**
```php
// Événement
class UserRegistered extends Event
{
    public function __construct(public User $user) {}
}

// Listeners
class SendWelcomeEmail implements ListenerInterface
{
    public function handle(UserRegistered $event): void
    {
        // Envoi de l'email de bienvenue
        Mail::send('emails.welcome', ['user' => $event->user]);
    }
}

class CreateUserProfile implements ListenerInterface
{
    public function handle(UserRegistered $event): void
    {
        // Création du profil utilisateur
        Profile::create(['user_id' => $event->user->id]);
    }
}

// Dans le service
class UserService
{
    public function register(array $data): User
    {
        $user = User::create($data);
        
        // Déclencher l'événement
        event(new UserRegistered($user));
        
        return $user;
    }
}
```

### 5. Strategy Pattern

✅ **Stratégies pour différents algorithmes :**
```php
interface ShippingCalculatorInterface
{
    public function calculate(Order $order): float;
}

class StandardShipping implements ShippingCalculatorInterface
{
    public function calculate(Order $order): float
    {
        return $order->weight * 0.5;
    }
}

class ExpressShipping implements ShippingCalculatorInterface
{
    public function calculate(Order $order): float
    {
        return $order->weight * 1.5 + 10;
    }
}

class ShippingService
{
    public function calculateShipping(Order $order, string $method): float
    {
        $calculator = match($method) {
            'standard' => new StandardShipping(),
            'express' => new ExpressShipping(),
            'overnight' => new OvernightShipping(),
            default => throw new InvalidArgumentException("Unknown shipping method: {$method}")
        };
        
        return $calculator->calculate($order);
    }
}
```

---

## Anti-Patterns à Éviter

### 1. God Object

❌ **Éviter les classes trop importantes :**
```php
// MAUVAIS : Classe qui fait tout
class UserManager
{
    public function createUser($data) { /* ... */ }
    public function updateUser($id, $data) { /* ... */ }
    public function deleteUser($id) { /* ... */ }
    public function sendEmail($user, $template) { /* ... */ }
    public function processPayment($user, $amount) { /* ... */ }
    public function generateReport($user) { /* ... */ }
    public function validateUserData($data) { /* ... */ }
    public function hashPassword($password) { /* ... */ }
    public function uploadAvatar($user, $file) { /* ... */ }
    // ... 50 autres méthodes
}
```

✅ **Mieux : Séparer les responsabilités :**
```php
class UserService { /* Gestion des utilisateurs */ }
class EmailService { /* Envoi d'emails */ }
class PaymentService { /* Traitement des paiements */ }
class ReportService { /* Génération de rapports */ }
class ValidationService { /* Validation des données */ }
class FileUploadService { /* Upload de fichiers */ }
```

### 2. Anemic Domain Model

❌ **Modèles sans logique :**
```php
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    // Aucune logique métier
}

// Toute la logique dans les services
class UserService
{
    public function isActive(User $user): bool
    {
        return $user->status === 'active' && $user->email_verified_at !== null;
    }
    
    public function getFullName(User $user): string
    {
        return $user->first_name . ' ' . $user->last_name;
    }
}
```

✅ **Mieux : Modèles riches :**
```php
class User extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email', 'password'];
    
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->email_verified_at !== null;
    }
    
    public function getFullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }
    
    public function canAccessAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('moderator');
    }
}
```

### 3. Magic Numbers et Strings

❌ **Éviter les valeurs magiques :**
```php
class OrderService
{
    public function processOrder(Order $order)
    {
        if ($order->status === 1) { // Qu'est-ce que 1 ?
            $order->discount = $order->total * 0.1; // 10% mais pourquoi ?
            
            if ($order->total > 100) { // 100 quoi ?
                $order->shipping = 0;
            }
        }
    }
}
```

✅ **Utiliser des constantes :**
```php
class OrderStatus
{
    public const PENDING = 1;
    public const CONFIRMED = 2;
    public const SHIPPED = 3;
    public const DELIVERED = 4;
    public const CANCELLED = 5;
}

class OrderService
{
    private const LOYALTY_DISCOUNT_RATE = 0.1;
    private const FREE_SHIPPING_THRESHOLD = 100.00;
    
    public function processOrder(Order $order)
    {
        if ($order->status === OrderStatus::PENDING) {
            $order->discount = $order->total * self::LOYALTY_DISCOUNT_RATE;
            
            if ($order->total >= self::FREE_SHIPPING_THRESHOLD) {
                $order->shipping = 0;
            }
        }
    }
}
```

### 4. Tight Coupling

❌ **Couplage fort :**
```php
class OrderController
{
    public function store(Request $request)
    {
        // Couplage direct avec des classes concrètes
        $emailService = new SMTPEmailService();
        $paymentService = new StripePaymentService();
        $inventoryService = new DatabaseInventoryService();
        
        // Difficile à tester et à modifier
    }
}
```

✅ **Couplage faible avec interfaces :**
```php
class OrderController
{
    public function __construct(
        private EmailServiceInterface $emailService,
        private PaymentServiceInterface $paymentService,
        private InventoryServiceInterface $inventoryService
    ) {}
    
    public function store(Request $request)
    {
        // Facile à tester avec des mocks
        // Facile à changer d'implémentation
    }
}
```

---

## Sécurité

### 1. Validation et Sanitisation

✅ **Toujours valider les entrées :**
```php
class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'age' => 'required|integer|min:13|max:120'
        ];
    }
    
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.'
        ];
    }
}
```

### 2. Protection CSRF

✅ **Utiliser la protection CSRF :**
```php
// Dans les routes
Router::group(['middleware' => 'csrf'], function() {
    Router::post('/users', 'UserController@store');
    Router::put('/users/{id}', 'UserController@update');
    Router::delete('/users/{id}', 'UserController@destroy');
});
```

### 3. Authentification Sécurisée

✅ **Hachage sécurisé des mots de passe :**
```php
class UserService
{
    public function createUser(array $data): User
    {
        $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
        
        return User::create($data);
    }
    
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
```

### 4. Protection contre l'Injection SQL

✅ **Utiliser les requêtes préparées :**
```php
// BON : Utilisation de l'ORM
$users = User::where('email', $email)->get();

// BON : Query Builder avec bindings
$users = DB::select('SELECT * FROM users WHERE email = ?', [$email]);

// BON : Requête nommée
$users = DB::select('SELECT * FROM users WHERE email = :email', ['email' => $email]);
```

❌ **Éviter la concaténation directe :**
```php
// DANGEREUX : Injection SQL possible
$users = DB::select("SELECT * FROM users WHERE email = '{$email}'");
```

### 5. Gestion des Permissions

✅ **Système de permissions granulaire :**
```php
class PostController extends Controller
{
    public function update(Request $request, Post $post)
    {
        // Vérification des permissions
        if (!$request->user()->can('update', $post)) {
            abort(403, 'Action non autorisée.');
        }
        
        $post->update($request->validated());
        
        return response()->json($post);
    }
}

// Policy
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->hasRole('admin');
    }
}
```

---

## Performance

### 1. Optimisation des Requêtes

✅ **Eager Loading :**
```php
// BON : Chargement anticipé
$posts = Post::with(['author', 'comments.author'])->get();

foreach ($posts as $post) {
    echo $post->author->name; // Pas de requête supplémentaire
    foreach ($post->comments as $comment) {
        echo $comment->author->name; // Pas de requête supplémentaire
    }
}
```

❌ **Éviter le N+1 Problem :**
```php
// MAUVAIS : N+1 requêtes
$posts = Post::all(); // 1 requête

foreach ($posts as $post) {
    echo $post->author->name; // N requêtes supplémentaires
}
```

### 2. Utilisation du Cache

✅ **Cache intelligent :**
```php
class PostService
{
    public function getPopularPosts(int $limit = 10): Collection
    {
        return Cache::remember('popular_posts', 3600, function () use ($limit) {
            return Post::withCount('views')
                      ->orderBy('views_count', 'desc')
                      ->limit($limit)
                      ->get();
        });
    }
    
    public function updatePost(Post $post, array $data): Post
    {
        $post->update($data);
        
        // Invalider le cache
        Cache::forget('popular_posts');
        Cache::forget("post.{$post->id}");
        
        return $post;
    }
}
```

### 3. Pagination

✅ **Toujours paginer les grandes collections :**
```php
class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with('author')
                    ->when($request->search, function ($query, $search) {
                        $query->where('title', 'like', "%{$search}%");
                    })
                    ->orderBy('created_at', 'desc')
                    ->paginate(15); // Pagination
        
        return view('posts.index', compact('posts'));
    }
}
```

---

## Tests

### 1. Structure des Tests

✅ **Tests bien organisés :**
```php
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private UserRepository $userRepository;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }
    
    /** @test */
    public function it_creates_a_user_with_valid_data()
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];
        
        $expectedUser = new User($userData);
        
        $this->userRepository
             ->expects($this->once())
             ->method('create')
             ->with($userData)
             ->willReturn($expectedUser);
        
        // Act
        $result = $this->userService->createUser($userData);
        
        // Assert
        $this->assertEquals($expectedUser, $result);
    }
    
    /** @test */
    public function it_throws_exception_when_email_already_exists()
    {
        // Arrange
        $userData = ['email' => 'existing@example.com'];
        
        $this->userRepository
             ->expects($this->once())
             ->method('findByEmail')
             ->with('existing@example.com')
             ->willReturn(new User());
        
        // Act & Assert
        $this->expectException(UserAlreadyExistsException::class);
        $this->userService->createUser($userData);
    }
}
```

### 2. Tests d'Intégration

✅ **Tests avec base de données :**
```php
class PostControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function authenticated_user_can_create_post()
    {
        // Arrange
        $user = User::factory()->create();
        $postData = [
            'title' => 'Test Post',
            'content' => 'This is a test post content.'
        ];
        
        // Act
        $response = $this->actingAs($user)
                         ->postJson('/api/posts', $postData);
        
        // Assert
        $response->assertStatus(201)
                 ->assertJson([
                     'title' => 'Test Post',
                     'author_id' => $user->id
                 ]);
        
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $user->id
        ]);
    }
}
```

---

## Conventions de Code

### 1. Nommage

✅ **Conventions de nommage :**
```php
// Classes : PascalCase
class UserController {}
class PaymentService {}

// Méthodes et variables : camelCase
public function getUserById(int $userId): ?User
{
    $foundUser = $this->userRepository->find($userId);
    return $foundUser;
}

// Constantes : SCREAMING_SNAKE_CASE
class OrderStatus
{
    public const PENDING_PAYMENT = 'pending_payment';
    public const PAYMENT_CONFIRMED = 'payment_confirmed';
}

// Fichiers et dossiers : snake_case
// create_users_table.php
// user_profile_controller.php
```

### 2. Documentation

✅ **Documentation complète :**
```php
/**
 * Service de gestion des commandes.
 * 
 * Gère la création, modification et traitement des commandes.
 */
class OrderService
{
    /**
     * Crée une nouvelle commande.
     * 
     * @param array $items Liste des articles avec quantités
     * @param User $user Utilisateur qui passe la commande
     * @param string|null $couponCode Code promo optionnel
     * 
     * @return Order La commande créée
     * 
     * @throws InsufficientStockException Si stock insuffisant
     * @throws InvalidCouponException Si le coupon est invalide
     */
    public function createOrder(array $items, User $user, ?string $couponCode = null): Order
    {
        // Implémentation...
    }
}
```

---

## Gestion des Erreurs

### 1. Exceptions Personnalisées

✅ **Hiérarchie d'exceptions :**
```php
// Exception de base
abstract class NexaException extends Exception
{
    protected int $statusCode = 500;
    
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

// Exceptions spécifiques
class ValidationException extends NexaException
{
    protected int $statusCode = 422;
    
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('Données de validation invalides');
    }
}

class ResourceNotFoundException extends NexaException
{
    protected int $statusCode = 404;
    
    public function __construct(string $resource, mixed $id)
    {
        parent::__construct("Ressource {$resource} avec l'ID {$id} non trouvée");
    }
}

class UnauthorizedException extends NexaException
{
    protected int $statusCode = 401;
}
```

### 2. Gestion Globale des Erreurs

✅ **Handler d'exceptions :**
```php
class ExceptionHandler
{
    public function handle(Throwable $exception): Response
    {
        // Log de l'erreur
        Logger::error('Exception capturée', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Réponse selon le type d'exception
        if ($exception instanceof ValidationException) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $exception->getErrors()
            ], 422);
        }
        
        if ($exception instanceof ResourceNotFoundException) {
            return response()->json([
                'error' => 'Resource not found',
                'message' => $exception->getMessage()
            ], 404);
        }
        
        // Erreur générique en production
        if (config('app.env') === 'production') {
            return response()->json([
                'error' => 'Internal server error'
            ], 500);
        }
        
        // Détails complets en développement
        return response()->json([
            'error' => 'Internal server error',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ], 500);
    }
}
```

---

## Conclusion

Ces meilleures pratiques vous aideront à :

- **Maintenir un code propre** et facilement compréhensible
- **Améliorer la sécurité** de vos applications
- **Optimiser les performances** 
- **Faciliter les tests** et la maintenance
- **Assurer l'évolutivité** de vos projets

N'oubliez pas que ces pratiques doivent être adaptées au contexte de votre projet. L'important est de rester cohérent dans votre équipe et de toujours privilégier la lisibilité et la maintenabilité du code.