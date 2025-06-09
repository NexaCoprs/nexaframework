<?php

namespace Nexa\Modules;

use Nexa\Core\Application;

/**
 * Classe de base pour tous les modules Nexa
 * 
 * Cette classe abstraite définit l'interface et les fonctionnalités de base
 * que tous les modules Nexa doivent implémenter.
 * 
 * @package Nexa\Modules
 */
abstract class Module
{
    /**
     * Instance de l'application Nexa
     *
     * @var \Nexa\Core\Application
     */
    protected $app;

    /**
     * Nom du module
     *
     * @var string
     */
    protected $name;

    /**
     * Version du module
     *
     * @var string
     */
    protected $version;

    /**
     * Description du module
     *
     * @var string
     */
    protected $description;

    /**
     * Auteur du module
     *
     * @var string
     */
    protected $author;

    /**
     * Dépendances du module
     *
     * @var array
     */
    protected $dependencies = [];

    /**
     * Indique si le module est activé
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Chemin vers le répertoire du module
     *
     * @var string
     */
    protected $path;

    /**
     * Constructeur
     *
     * @param \Nexa\Core\Application $app Instance de l'application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Méthode appelée lors de l'enregistrement du module
     * 
     * Cette méthode est appelée une seule fois lors du chargement initial du module.
     * Utilisez cette méthode pour enregistrer des liaisons dans le conteneur,
     * des fournisseurs de services, etc.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Méthode appelée lors du démarrage du module
     * 
     * Cette méthode est appelée après que tous les modules ont été enregistrés.
     * Utilisez cette méthode pour effectuer des actions qui dépendent d'autres modules.
     *
     * @return void
     */
    abstract public function boot(): void;

    /**
     * Méthode appelée lors de l'activation du module
     * 
     * Cette méthode est appelée lorsque le module est activé par l'utilisateur.
     * Utilisez cette méthode pour effectuer des actions d'initialisation comme
     * la création de tables en base de données, etc.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->enabled = true;
        
        // Exécuter les migrations si configuré et si app est disponible
        if ($this->app && $this->app['config']->get('modules.migrations.run_on_enable', true)) {
            $this->runMigrations();
        }
    }

    /**
     * Méthode appelée lors de la désactivation du module
     * 
     * Cette méthode est appelée lorsque le module est désactivé par l'utilisateur.
     * Utilisez cette méthode pour effectuer des actions de nettoyage.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->enabled = false;
        
        // Annuler les migrations si configuré et si app est disponible
        if ($this->app && $this->app['config']->get('modules.migrations.rollback_on_disable', false)) {
            $this->rollbackMigrations();
        }
    }

    /**
     * Méthode appelée lors de l'installation du module
     * 
     * Cette méthode est appelée lorsque le module est installé pour la première fois.
     * Utilisez cette méthode pour effectuer des actions d'initialisation comme
     * la création de tables en base de données, etc.
     *
     * @return void
     */
    public function install(): void
    {
        // Implémentation par défaut vide
    }

    /**
     * Méthode appelée lors de la désinstallation du module
     * 
     * Cette méthode est appelée lorsque le module est désinstallé par l'utilisateur.
     * Utilisez cette méthode pour effectuer des actions de nettoyage complètes comme
     * la suppression de tables en base de données, etc.
     *
     * @return void
     */
    public function uninstall(): void
    {
        // Implémentation par défaut vide
    }

    /**
     * Méthode appelée lors de la mise à jour du module
     * 
     * Cette méthode est appelée lorsque le module est mis à jour vers une nouvelle version.
     * Utilisez cette méthode pour effectuer des migrations de données, etc.
     *
     * @param string $oldVersion Ancienne version du module
     * @param string $newVersion Nouvelle version du module
     * @return void
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        $this->version = $newVersion;
    }

    /**
     * Exécute les migrations du module
     *
     * @return void
     */
    protected function runMigrations(): void
    {
        if (!$this->app) {
            return;
        }
        
        $migrationPath = $this->getPath() . '/Database/Migrations';
        
        if (is_dir($migrationPath)) {
            $this->app['migrator']->run($migrationPath);
        }
    }

    /**
     * Annule les migrations du module
     *
     * @return void
     */
    protected function rollbackMigrations(): void
    {
        if (!$this->app) {
            return;
        }
        
        $migrationPath = $this->getPath() . '/Database/Migrations';
        
        if (is_dir($migrationPath)) {
            $this->app['migrator']->rollback($migrationPath);
        }
    }

    /**
     * Charge les routes du module
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $routesPath = $this->getPath() . '/Routes';
        
        if (is_dir($routesPath)) {
            $files = glob($routesPath . '/*.php');
            
            foreach ($files as $file) {
                require_once $file;
            }
        }
    }

    /**
     * Charge les vues du module
     *
     * @return void
     */
    public function loadViews(): void
    {
        if (!$this->app) {
            return;
        }
        
        $viewsPath = $this->getPath() . '/Views';
        
        if (is_dir($viewsPath)) {
            $this->app['view']->addNamespace($this->getName(), $viewsPath);
        }
    }

    /**
     * Charge les traductions du module
     *
     * @return void
     */
    public function loadTranslations(): void
    {
        if (!$this->app) {
            return;
        }
        
        $langPath = $this->getPath() . '/Lang';
        
        if (is_dir($langPath)) {
            $this->app['translator']->addNamespace($this->getName(), $langPath);
        }
    }

    /**
     * Charge les configurations du module
     *
     * @return void
     */
    public function loadConfigs(): void
    {
        if (!$this->app) {
            return;
        }
        
        $configPath = $this->getPath() . '/Config';
        
        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');
            
            foreach ($files as $file) {
                $name = basename($file, '.php');
                $this->app['config']->set($this->getName() . '.' . $name, require $file);
            }
        }
    }

    /**
     * Publie les assets du module
     *
     * @return void
     */
    public function publishAssets(): void
    {
        // Skip asset publishing if app is not available (e.g., in tests)
        if (!$this->app) {
            return;
        }
        
        $assetsPath = $this->getPath() . '/Assets';
        $publishPath = $this->app['config']->get('modules.assets.destination', public_path('modules')) . '/' . $this->getName();
        
        if (is_dir($assetsPath)) {
            $this->app['files']->copyDirectory($assetsPath, $publishPath);
        }
    }

    /**
     * Vérifie si le module est compatible avec la version actuelle du framework
     *
     * @return bool
     */
    public function isCompatible(): bool
    {
        // Par défaut, on considère que le module est compatible
        return true;
    }

    /**
     * Vérifie si toutes les dépendances du module sont satisfaites
     *
     * @return bool
     */
    public function checkDependencies(): bool
    {
        // Vérification à implémenter par le gestionnaire de modules
        return true;
    }

    /**
     * Retourne le nom du module
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne la version du module
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Retourne la description du module
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne l'auteur du module
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Retourne les dépendances du module
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Vérifie si le module est activé
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Définit si le module est activé
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Retourne le chemin vers le répertoire du module
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Définit le chemin vers le répertoire du module
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Vérifie si le module a des routes
     *
     * @return bool
     */
    public function hasRoutes(): bool
    {
        $routesPath = $this->getPath() . '/Routes';
        return is_dir($routesPath) && !empty(glob($routesPath . '/*.php'));
    }

    /**
     * Retourne la liste des migrations du module
     *
     * @return array
     */
    public function getMigrations(): array
    {
        // Check both possible migration paths
        $migrationPaths = [
            $this->getPath() . '/Database/Migrations',
            $this->getPath() . '/Migrations'
        ];
        
        $allFiles = [];
        
        foreach ($migrationPaths as $migrationPath) {
            if (is_dir($migrationPath)) {
                $files = glob($migrationPath . '/*.php');
                $allFiles = array_merge($allFiles, $files);
            }
        }
        
        return array_map('basename', $allFiles);
    }

    /**
     * Retourne le namespace du module
     *
     * @return string
     */
    public function getNamespace(): string
    {
        // Convertir le nom en namespace valide (sans espaces, PascalCase)
        return str_replace(' ', '', ucwords($this->getName()));
    }

    /**
     * Retourne la configuration du module
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'name' => $this->getName(),
            'version' => $this->getVersion(),
            'description' => $this->getDescription(),
            'author' => $this->getAuthor(),
            'dependencies' => $this->getDependencies(),
            'enabled' => $this->isEnabled()
        ];
    }
}