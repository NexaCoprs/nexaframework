# Phase 2 - Nexa Framework Roadmap

Ce document détaille le plan de développement pour la Phase 2 du framework Nexa, construisant sur les fondations solides établies en Phase 1.

## 🎯 Objectifs de la Phase 2

La Phase 2 vise à transformer Nexa en un framework full-stack moderne avec des capacités avancées d'API, d'événements, et de traitement asynchrone.

## 🚀 Fonctionnalités Planifiées

### 1. API REST avec Authentification JWT

#### Objectifs :
- Créer un système d'API RESTful complet
- Implémenter l'authentification JWT (JSON Web Tokens)
- Gestion des permissions et rôles
- Documentation automatique des APIs

#### Classes à créer :
- `src/Nexa/Auth/JWTManager.php` - Gestion des tokens JWT
- `src/Nexa/Auth/AuthServiceProvider.php` - Service d'authentification
- `src/Nexa/Http/Middleware/JWTMiddleware.php` - Middleware JWT
- `src/Nexa/Api/ApiController.php` - Contrôleur de base pour les APIs
- `src/Nexa/Api/ApiResponse.php` - Formatage des réponses API
- `src/Nexa/Auth/User.php` - Modèle utilisateur étendu

#### Fonctionnalités :
- Génération et validation de tokens JWT
- Refresh tokens pour la sécurité
- Rate limiting par utilisateur
- Versioning des APIs
- Pagination automatique
- Filtrage et tri des ressources

### 2. Système d'Événements et Listeners

#### Objectifs :
- Architecture événementielle découplée
- Système de hooks pour l'extensibilité
- Gestion asynchrone des événements

#### Classes à créer :
- `src/Nexa/Events/EventDispatcher.php` - Gestionnaire d'événements
- `src/Nexa/Events/Event.php` - Classe de base pour les événements
- `src/Nexa/Events/Listener.php` - Interface pour les listeners
- `src/Nexa/Events/EventServiceProvider.php` - Service provider

#### Fonctionnalités :
- Dispatch d'événements synchrones et asynchrones
- Listeners avec priorités
- Événements système (model events, request events)
- Subscribers pour grouper les listeners

### 3. Système de Queue pour Tâches Asynchrones

#### Objectifs :
- Traitement en arrière-plan
- Gestion des tâches longues
- Retry automatique en cas d'échec
- Monitoring des jobs

#### Classes à créer :
- `src/Nexa/Queue/QueueManager.php` - Gestionnaire de queues
- `src/Nexa/Queue/Job.php` - Classe de base pour les jobs
- `src/Nexa/Queue/Worker.php` - Worker pour traiter les jobs
- `src/Nexa/Queue/Drivers/DatabaseQueue.php` - Driver base de données
- `src/Nexa/Queue/Drivers/RedisQueue.php` - Driver Redis

#### Fonctionnalités :
- Multiple drivers (Database, Redis, File)
- Delayed jobs
- Job batching
- Failed job handling
- Queue monitoring dashboard

### 4. Tests Automatisés

#### Objectifs :
- Framework de tests intégré
- Tests unitaires et d'intégration
- Mocking et fixtures
- Coverage reporting

#### Classes à créer :
- `src/Nexa/Testing/TestCase.php` - Classe de base pour les tests
- `src/Nexa/Testing/DatabaseTestCase.php` - Tests avec base de données
- `src/Nexa/Testing/MockFactory.php` - Factory pour les mocks
- `src/Nexa/Testing/Assertions.php` - Assertions personnalisées

#### Fonctionnalités :
- Test runner intégré
- Database seeding pour les tests
- HTTP testing helpers
- Mocking des services externes

### 5. CLI Avancée

#### Objectifs :
- Interface en ligne de commande complète
- Génération de code automatique
- Outils de développement
- Commandes de maintenance

#### Classes à créer :
- `src/Nexa/Console/Application.php` - Application console
- `src/Nexa/Console/Command.php` - Classe de base pour les commandes
- `src/Nexa/Console/Generators/` - Générateurs de code
- `src/Nexa/Console/Commands/` - Commandes système

#### Commandes planifiées :
- `nexa make:controller` - Générer un contrôleur
- `nexa make:model` - Générer un modèle
- `nexa make:middleware` - Générer un middleware
- `nexa make:migration` - Générer une migration
- `nexa make:job` - Générer un job
- `nexa make:event` - Générer un événement
- `nexa make:listener` - Générer un listener
- `nexa serve` - Serveur de développement
- `nexa queue:work` - Worker de queue
- `nexa test` - Lancer les tests

## 📋 Plan de Développement

### Étape 1 : Authentification JWT (Semaines 1-2)
1. Implémentation du JWTManager
2. Middleware d'authentification
3. Endpoints d'authentification (/login, /register, /refresh)
4. Tests unitaires

### Étape 2 : API REST (Semaines 3-4)
1. Contrôleurs API de base
2. Formatage des réponses
3. Gestion des erreurs API
4. Documentation automatique

### Étape 3 : Système d'Événements (Semaines 5-6)
1. EventDispatcher et classes de base
2. Intégration avec le framework
3. Événements système
4. Documentation et exemples

### Étape 4 : Système de Queue (Semaines 7-8)
1. QueueManager et drivers
2. Worker et job processing
3. Interface de monitoring
4. Commandes CLI pour les queues

### Étape 5 : Tests et CLI (Semaines 9-10)
1. Framework de tests
2. Commandes CLI essentielles
3. Générateurs de code
4. Documentation complète

## 🔧 Configuration Requise

### Nouvelles dépendances :
```json
{
    "firebase/php-jwt": "^6.0",
    "predis/predis": "^2.0",
    "symfony/console": "^6.0",
    "phpunit/phpunit": "^10.0"
}
```

### Nouveaux fichiers de configuration :
- `config/auth.php` - Configuration d'authentification
- `config/queue.php` - Configuration des queues
- `config/events.php` - Configuration des événements
- `config/api.php` - Configuration des APIs

## 🎯 Critères de Succès

- [ ] API REST fonctionnelle avec authentification JWT
- [ ] Système d'événements opérationnel
- [ ] Queue system avec multiple drivers
- [ ] Suite de tests complète (>80% coverage)
- [ ] CLI avec toutes les commandes essentielles
- [ ] Documentation complète et exemples
- [ ] Performance maintenue ou améliorée
- [ ] Rétrocompatibilité avec Phase 1

## 🚀 Démarrage de la Phase 2

Pour commencer la Phase 2 :

1. **Préparation de l'environnement**
   ```bash
   composer require firebase/php-jwt predis/predis symfony/console phpunit/phpunit
   ```

2. **Structure des dossiers**
   ```
   src/Nexa/
   ├── Auth/
   ├── Api/
   ├── Events/
   ├── Queue/
   ├── Testing/
   └── Console/
   ```

3. **Première implémentation** : Commencer par le système JWT

---

*Ce roadmap est un document vivant qui sera mis à jour au fur et à mesure de l'avancement de la Phase 2.*