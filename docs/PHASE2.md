# Nexa Framework - Phase 2 Documentation

## Vue d'ensemble

La Phase 2 du Nexa Framework introduit des fonctionnalités avancées qui transforment le framework en une solution complète pour le développement d'applications web modernes. Cette phase ajoute l'authentification JWT, un système d'événements, un système de files d'attente, un framework de tests automatisés et une interface en ligne de commande.

## Nouvelles Fonctionnalités

### 1. Système d'Authentification JWT

#### Fonctionnalités
- Génération et validation de tokens JWT
- Gestion des tokens de rafraîchissement
- Blacklist des tokens
- Middleware d'authentification
- Gestion des exceptions JWT

#### Utilisation

```php
// Génération d'un token
$jwt = new JWT();
$token = $jwt->generateToken(['user_id' => 123]);

// Validation d'un token
$payload = $jwt->validateToken($token);

// Génération d'une paire de tokens
$tokens = $jwt->generateTokenPair(['user_id' => 123]);

// Rafraîchissement d'un token
$newTokens = $jwt->refreshToken($refreshToken);
```

#### Configuration

La configuration JWT se trouve dans `config/phase2.php` :

```php
'jwt' => [
    'secret' => 'your-secret-key',
    'algorithm' => 'HS256',
    'access_token_ttl' => 3600,
    'refresh_token_ttl' => 604800,
    // ...
]
```

### 2. Système d'Événements

#### Fonctionnalités
- Dispatcher d'événements avec priorités
- Listeners avec support des closures
- Événements prédéfinis pour l'authentification et la base de données
- Propagation d'événements avec possibilité d'arrêt
- Logging automatique des événements

#### Classes Principales
- `Event` : Classe de base pour tous les événements
- `EventDispatcher` : Gestionnaire principal des événements
- `UserEvents` : Événements liés aux utilisateurs
- `DatabaseEvents` : Événements liés à la base de données

#### Utilisation

```php
// Enregistrement d'un listener
$dispatcher = new EventDispatcher();
$dispatcher->listen('UserRegistered', function($event) {
    // Logique du listener
});

// Déclenchement d'un événement
$event = new UserRegistered($user);
$dispatcher->dispatch($event);

// Listener avec priorité
$dispatcher->listen('UserLoggedIn', $listener, 100);
```

#### Événements Prédéfinis

**Événements Utilisateur :**
- `UserRegistered`
- `UserLoggedIn`
- `UserLoggedOut`
- `UserProfileUpdated`
- `UserPasswordChanged`
- `UserDeleted`

**Événements Base de Données :**
- `ModelCreating` / `ModelCreated`
- `ModelUpdating` / `ModelUpdated`
- `ModelDeleting` / `ModelDeleted`
- `DatabaseQuery`

### 3. Système de Files d'Attente

#### Fonctionnalités
- Support de multiples drivers (Sync, Database)
- Jobs avec retry automatique
- Gestion des échecs
- Jobs différés
- Interface en ligne de commande pour le traitement

#### Classes Principales
- `JobInterface` : Interface pour tous les jobs
- `Job` : Classe abstraite de base pour les jobs
- `QueueManager` : Gestionnaire principal des files d'attente
- `QueueDriverInterface` : Interface pour les drivers
- `SyncQueueDriver` : Driver synchrone
- `DatabaseQueueDriver` : Driver base de données

#### Utilisation

```php
// Création d'un job
class SendEmailJob extends Job {
    public function handle() {
        // Logique d'envoi d'email
    }
}

// Ajout à la file d'attente
$queueManager = new QueueManager($config);
$job = new SendEmailJob(['to' => 'user@example.com']);
$queueManager->push($job);

// Job différé
$queueManager->push($job, 'default', 300); // 5 minutes de délai
```

#### Jobs Prédéfinis
- `SendEmailJob` : Envoi d'emails
- `ProcessImageJob` : Traitement d'images

### 4. Framework de Tests Automatisés

#### Fonctionnalités
- Classe de base `TestCase` avec assertions
- Runner de tests avec rapports
- Support de multiples formats de rapport (JSON, XML, HTML)
- Gestion des transactions de base de données pour les tests
- Tests d'intégration et unitaires

#### Classes Principales
- `TestCase` : Classe de base pour tous les tests
- `TestRunner` : Exécuteur de tests avec rapports

#### Utilisation

```php
class MyTest extends TestCase {
    public function testSomething() {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
        $this->assertNotNull($value);
    }
    
    public function testException() {
        $this->expectException(InvalidArgumentException::class);
        // Code qui doit lever une exception
    }
}

// Exécution des tests
$runner = new TestRunner();
$runner->addTestClass(MyTest::class);
$runner->runAllTests();
```

#### Tests Prédéfinis
- `AuthTest` : Tests pour l'authentification JWT
- `QueueTest` : Tests pour le système de files d'attente
- `EventTest` : Tests pour le système d'événements

### 5. Interface en Ligne de Commande (CLI)

#### Fonctionnalités
- Commandes pour les tests, files d'attente, migrations
- Génération de composants (contrôleurs, modèles, etc.)
- Serveur de développement
- Gestion du cache et des logs

#### Commandes Disponibles

```bash
# Tests
./nexa test                    # Exécuter tous les tests
./nexa test AuthTest          # Exécuter une classe de test spécifique
./nexa test AuthTest::testJWT # Exécuter un test spécifique

# Files d'attente
./nexa queue:work             # Traiter les jobs en continu
./nexa queue:process          # Traiter un job
./nexa queue:clear            # Vider la file d'attente
./nexa queue:failed           # Voir les jobs échoués
./nexa queue:retry            # Relancer les jobs échoués

# Serveur de développement
./nexa serve                  # Démarrer le serveur (port 8000)
./nexa serve --port=3000      # Démarrer sur un port spécifique

# Génération
./nexa make:controller UserController
./nexa make:model User
./nexa make:middleware AuthMiddleware
./nexa make:job ProcessPaymentJob
./nexa make:listener UserRegisteredListener
./nexa make:test UserTest

# Utilitaires
./nexa jwt:secret             # Générer une clé secrète JWT
./nexa cache:clear            # Vider le cache
./nexa logs:clear             # Vider les logs
./nexa env:check              # Vérifier l'environnement
./nexa version                # Afficher la version
```

## Configuration

### Fichier de Configuration Principal

La configuration de la Phase 2 se trouve dans `config/phase2.php`. Ce fichier contient toutes les configurations pour :

- JWT Authentication
- Système d'événements
- Files d'attente
- Tests
- CLI
- Sécurité
- Performance
- Intégrations

### Variables d'Environnement

Ajoutez ces variables à votre fichier `.env` :

```env
# JWT
JWT_SECRET=your-very-secure-secret-key-here
JWT_ISSUER=your-app-name
JWT_AUDIENCE=your-app-users

# Queue
QUEUE_DRIVER=database

# Events
EVENT_LOGGING=true

# Testing
TEST_DB_DRIVER=sqlite
TEST_DB_NAME=:memory:

# Email
MAIL_DRIVER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Your App Name"

# CORS
CORS_ALLOWED_ORIGINS=*

# Monitoring
MONITORING_ENABLED=false
```

## Architecture

### Structure des Dossiers

```
nexframework/
├── src/
│   ├── Auth/              # Système d'authentification JWT
│   ├── Events/            # Système d'événements
│   ├── Queue/             # Système de files d'attente
│   └── Testing/           # Framework de tests
├── tests/                 # Tests automatisés
│   ├── AuthTest.php
│   ├── QueueTest.php
│   └── EventTest.php
├── config/
│   └── phase2.php         # Configuration Phase 2
├── docs/
│   └── PHASE2.md          # Cette documentation
├── nexa                   # CLI exécutable
└── NexaCLI.php           # Classe CLI principale
```

### Intégration avec la Phase 1

La Phase 2 s'intègre parfaitement avec les fonctionnalités de la Phase 1 :

- **Routage** : Les middlewares JWT peuvent être appliqués aux routes
- **Base de données** : Les événements de base de données sont automatiquement déclenchés
- **Logging** : Tous les systèmes utilisent le logger existant
- **Configuration** : Extension du système de configuration existant

## Exemples d'Utilisation

### Application Complète avec Authentification

```php
// routes/web.php
$router->post('/api/login', function($request) {
    $jwt = new JWT();
    $user = authenticate($request->email, $request->password);
    
    if ($user) {
        $tokens = $jwt->generateTokenPair(['user_id' => $user->id]);
        
        // Déclencher un événement
        $event = new UserLoggedIn($user);
        $dispatcher->dispatch($event);
        
        return json_encode($tokens);
    }
    
    return json_encode(['error' => 'Invalid credentials']);
});

// Route protégée
$router->get('/api/profile', function($request) {
    $user = $request->user(); // Fourni par le middleware JWT
    return json_encode($user);
})->middleware('jwt');
```

### Traitement Asynchrone avec Files d'Attente

```php
// Lors de l'inscription d'un utilisateur
$user = new User($data);
$user->save();

// Déclencher un événement
$event = new UserRegistered($user);
$dispatcher->dispatch($event);

// Listener qui ajoute un job à la file d'attente
$dispatcher->listen('UserRegistered', function($event) use ($queueManager) {
    $job = new SendEmailJob([
        'to' => $event->getUser()->email,
        'template' => 'welcome',
        'data' => ['name' => $event->getUser()->name]
    ]);
    
    $queueManager->push($job, 'emails');
});
```

### Tests d'Intégration

```php
class UserRegistrationTest extends TestCase {
    public function testUserRegistrationFlow() {
        // Préparer les données
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];
        
        // Simuler une requête
        $request = $this->createRequest('POST', '/api/register', $userData);
        
        // Exécuter
        $response = $this->app->handle($request);
        
        // Assertions
        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        
        // Vérifier que l'événement a été déclenché
        $this->assertEventDispatched('UserRegistered');
        
        // Vérifier que le job a été ajouté à la file d'attente
        $this->assertJobQueued(SendEmailJob::class);
    }
}
```

## Bonnes Pratiques

### Sécurité

1. **JWT Secret** : Utilisez une clé secrète forte et unique
2. **Token Expiration** : Configurez des durées de vie appropriées
3. **Blacklist** : Activez la blacklist pour les tokens révoqués
4. **HTTPS** : Utilisez toujours HTTPS en production
5. **Rate Limiting** : Implémentez une limitation du taux de requêtes

### Performance

1. **Cache** : Activez le cache pour les événements et routes
2. **Queue Workers** : Utilisez plusieurs workers pour les files d'attente
3. **Database Indexing** : Indexez les colonnes utilisées par les jobs
4. **Memory Management** : Surveillez l'utilisation mémoire des jobs

### Tests

1. **Isolation** : Chaque test doit être indépendant
2. **Transactions** : Utilisez les transactions pour les tests de base de données
3. **Mocking** : Moquez les services externes
4. **Coverage** : Visez une couverture de code élevée

### Événements

1. **Naming** : Utilisez des noms d'événements descriptifs
2. **Data** : Incluez toutes les données nécessaires dans l'événement
3. **Listeners** : Gardez les listeners simples et rapides
4. **Async** : Utilisez les files d'attente pour les traitements longs

## Migration depuis la Phase 1

Pour migrer une application existante de la Phase 1 vers la Phase 2 :

1. **Configuration** : Ajoutez le fichier `config/phase2.php`
2. **Variables d'environnement** : Mettez à jour votre `.env`
3. **Base de données** : Créez les tables pour les files d'attente
4. **Middleware** : Ajoutez les middlewares JWT aux routes protégées
5. **Tests** : Migrez vos tests existants vers le nouveau framework

## Dépannage

### Problèmes Courants

1. **JWT Invalid** : Vérifiez la clé secrète et l'algorithme
2. **Queue Jobs Failing** : Vérifiez les logs et la configuration de la base de données
3. **Events Not Firing** : Vérifiez l'enregistrement des listeners
4. **Tests Failing** : Vérifiez la configuration de la base de données de test

### Logs

Tous les composants de la Phase 2 utilisent le système de logging :

- JWT : `logs/jwt.log`
- Events : `logs/events.log`
- Queue : `logs/queue.log`
- Tests : `logs/tests.log`

## Conclusion

La Phase 2 du Nexa Framework fournit une base solide pour développer des applications web modernes avec authentification, traitement asynchrone, système d'événements et tests automatisés. Ces fonctionnalités travaillent ensemble pour créer une architecture robuste et maintenable.

Pour plus d'informations, consultez les exemples de code dans le dossier `tests/` et les fichiers de configuration dans `config/`.