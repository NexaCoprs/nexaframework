# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Versioning Sémantique](https://semver.org/spec/v2.0.0.html).

## [Non Publié]

### Ajouté
- Documentation complète du framework
- Guide de démarrage rapide
- Guide des meilleures pratiques
- Guide de contribution
- Tutoriels détaillés

### Modifié
- Amélioration de la page À propos avec animations
- Correction des dates de développement du framework

---

## [1.0.0] - 2025-06-15

### Ajouté

#### Phase 1 - Fonctionnalités de Base
- **Système de Routage** avancé avec support des groupes et middleware
- **ORM Eloquent-like** avec relations et query builder
- **Système de Contrôleurs** avec injection de dépendances
- **Moteur de Templates** (.nx) avec syntaxe intuitive
- **Système de Validation** complet avec règles personnalisées
- **Middleware** pour l'authentification, CORS, rate limiting
- **Gestion des Sessions** sécurisée
- **Système de Cache** multi-drivers (File, Redis, Memcached)
- **Logger** compatible PSR-3
- **Gestion des Erreurs** avec pages d'erreur personnalisées
- **CLI** avec commandes artisan-like
- **Configuration** centralisée avec support .env
- **Service Container** avec injection de dépendances

#### Phase 2 - Fonctionnalités Avancées
- **Authentification JWT** complète
- **Système d'Événements** avec listeners
- **Queue System** pour les tâches asynchrones
- **API REST** avec resources et transformers
- **Rate Limiting** avancé
- **Notifications** multi-canaux (email, SMS, push)
- **File Storage** avec drivers multiples
- **Pagination** intelligente
- **Localization** (i18n) complète
- **Testing Suite** avec helpers et mocks

#### Phase 3 - Architecture Modulaire
- **Système de Plugins** extensible
- **Architecture Modulaire** avec packages
- **GraphQL API** intégrée
- **WebSockets** en temps réel
- **Microservices** support
- **Docker** configuration
- **CI/CD** pipelines
- **Monitoring** et métriques
- **Documentation** auto-générée
- **Performance** optimisations

### Sécurité
- Protection CSRF intégrée
- Validation et sanitisation automatique des entrées
- Hachage sécurisé des mots de passe (Argon2ID)
- Protection contre l'injection SQL
- Headers de sécurité automatiques
- Rate limiting pour prévenir les attaques
- Validation des tokens JWT
- Chiffrement des données sensibles

---

## [0.9.0] - 2025-05-30

### Ajouté
- Version bêta du framework
- Tests d'intégration complets
- Documentation API de base
- Exemples d'utilisation

### Modifié
- Optimisation des performances du routeur
- Amélioration de la gestion des erreurs
- Refactorisation du système de cache

### Corrigé
- Problèmes de compatibilité PHP 8.1+
- Fuites mémoire dans le query builder
- Bugs de validation des formulaires

---

## [0.8.0] - 2025-05-15

### Ajouté
- Système de middleware complet
- Support des relations ORM avancées
- CLI avec génération de code
- Système de migrations

### Modifié
- Architecture du container IoC
- Performance du moteur de templates
- Structure des fichiers de configuration

---

## [0.7.0] - 2025-05-01

### Ajouté
- ORM avec query builder
- Système de validation
- Gestion des sessions
- Support des bases de données multiples

### Modifié
- Refactorisation complète du routeur
- Amélioration du système de contrôleurs

---

## [0.6.0] - 2025-04-15

### Ajouté
- Moteur de templates .nx
- Système de cache
- Logger PSR-3
- Configuration centralisée

---

## [0.5.0] - 2025-04-01

### Ajouté
- Système de routage de base
- Contrôleurs avec injection de dépendances
- Service container
- Gestion des erreurs

---

## [0.4.0] - 2025-03-15

### Ajouté
- Architecture MVC de base
- Autoloader PSR-4
- Structure de projet
- Tests unitaires de base

---

## [0.3.0] - 2025-03-01

### Ajouté
- Prototype du routeur
- Classes de base HTTP
- Configuration initiale

---

## [0.2.0] - 2025-02-15

### Ajouté
- Structure de base du framework
- Namespace et autoloading
- Première version du container

---

## [0.1.0] - 2025-02-01

### Ajouté
- Initialisation du projet
- Concept et architecture de base
- Première version du README
- Configuration Composer

---

## Types de Changements

- **Ajouté** pour les nouvelles fonctionnalités
- **Modifié** pour les changements dans les fonctionnalités existantes
- **Déprécié** pour les fonctionnalités qui seront supprimées prochainement
- **Supprimé** pour les fonctionnalités supprimées
- **Corrigé** pour les corrections de bugs
- **Sécurité** pour les vulnérabilités corrigées

---

## Liens

- [Non Publié]: https://github.com/nexa-framework/nexa/compare/v1.0.0...HEAD
- [1.0.0]: https://github.com/nexa-framework/nexa/compare/v0.9.0...v1.0.0
- [0.9.0]: https://github.com/nexa-framework/nexa/compare/v0.8.0...v0.9.0
- [0.8.0]: https://github.com/nexa-framework/nexa/compare/v0.7.0...v0.8.0
- [0.7.0]: https://github.com/nexa-framework/nexa/compare/v0.6.0...v0.7.0
- [0.6.0]: https://github.com/nexa-framework/nexa/compare/v0.5.0...v0.6.0
- [0.5.0]: https://github.com/nexa-framework/nexa/compare/v0.4.0...v0.5.0
- [0.4.0]: https://github.com/nexa-framework/nexa/compare/v0.3.0...v0.4.0
- [0.3.0]: https://github.com/nexa-framework/nexa/compare/v0.2.0...v0.3.0
- [0.2.0]: https://github.com/nexa-framework/nexa/compare/v0.1.0...v0.2.0
- [0.1.0]: https://github.com/nexa-framework/nexa/releases/tag/v0.1.0