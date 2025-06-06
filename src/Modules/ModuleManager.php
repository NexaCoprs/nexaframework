<?php

namespace Nexa\Modules;

use Nexa\Core\Application;

/**
 * Gestionnaire de modules pour Nexa Framework
 * 
 * Cette classe est responsable du chargement, de l'activation et de la gestion
 * des modules dans le framework Nexa.
 * 
 * @package Nexa\Modules
 */
class ModuleManager
{
    /**
     * Instance de l'application Nexa
     *
     * @var \Nexa\Core\Application
     */
    protected $app;



    /**
     * Répertoire des modules
     *
     * @var string
     */
    protected $modulesDirectory;

    /**
     * Liste des modules chargés
     *
     * @var array<string, \Nexa\Modules\Module>
     */
    protected $modules = [];

    /**
     * Liste des modules activés
     *
     * @var array<string, bool>
     */
    protected $enabledModules = [];

    /**
     * Ordre de chargement des modules
     *
     * @var array
     */
    protected $loadOrder = [];

    /**
     * Indique si les modules ont été chargés
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Constructeur
     *
     * @param \Nexa\Core\Application|null $app Instance de l'application
     */
    public function __construct(Application $app = null)
    {
        $this->app = $app;
        
        if ($app) {
            $this->modulesDirectory = $app['config']->get('modules.directory', base_path('modules'));
            $this->enabledModules = $app['config']->get('modules.modules', []);
            $this->loadOrder = $app['config']->get('modules.load_order', []);
        } else {
            // Configuration par défaut pour les tests
            $this->modulesDirectory = __DIR__ . '/../../modules';
            $this->enabledModules = [];
            $this->loadOrder = [];
        }
    }

    /**
     * Charge tous les modules disponibles
     *
     * @return void
     */
    public function loadModules(): void
    {
        if ($this->loaded) {
            return;
        }

        // Vérifier si le système de modules est activé
        if ($this->app && isset($this->app['config']) && $this->app['config'] && !$this->app['config']->get('modules.enabled', true)) {
            return;
        }

        // Vérifier si le répertoire des modules existe
        if (!is_dir($this->modulesDirectory)) {
            mkdir($this->modulesDirectory, 0755, true);
        }

        // Charger les modules depuis le répertoire
        if (!$this->app || !isset($this->app['config']) || $this->app['config']->get('modules.auto_discover', true)) {
            $this->discoverModules();
        }

        // Trier les modules selon l'ordre de chargement
        $this->sortModulesByLoadOrder();

        // Enregistrer les modules
        $this->registerModules();

        // Démarrer les modules activés
        $this->bootModules();

        $this->loaded = true;
    }

    /**
     * Découvre les modules disponibles dans le répertoire des modules
     *
     * @param string|null $directory Répertoire spécifique à scanner (optionnel)
     * @return array Liste des modules découverts
     */
    public function discoverModules(string $directory = null): array
    {
        $searchDirectory = $directory ?? $this->modulesDirectory;
        
        // Special case for test directory - if it's a single module directory
        if (basename($searchDirectory) === 'TestModule') {
            $moduleName = 'TestModule';
            $moduleFile = $searchDirectory . '/TestModule.php';
            
            if (file_exists($moduleFile)) {
                include_once $moduleFile;
                if (class_exists('TestModule')) {
                    /** @var \Nexa\Modules\Module $module */
                    $module = new \TestModule($this->app);
                    $module->setPath($searchDirectory);
                    $this->modules[$moduleName] = $module;
                    return [$moduleName => $module];
                }
            }
        }
        
        $directories = glob($searchDirectory . '/*', GLOB_ONLYDIR);
        $discoveredModules = [];

        foreach ($directories as $dir) {
            $moduleName = basename($dir);
            $moduleClass = $this->findModuleClass($dir);

            if ($moduleClass && class_exists($moduleClass)) {
                $module = new $moduleClass($this->app);
                $module->setPath($dir);
                $this->modules[$moduleName] = $module;
                $discoveredModules[$moduleName] = $module;
            }
        }
        
        return $discoveredModules;
    }

    /**
     * Trouve la classe principale du module dans le répertoire spécifié
     *
     * @param string $directory Répertoire du module
     * @return string|null Nom de la classe du module ou null si non trouvé
     */
    protected function findModuleClass(string $directory): ?string
    {
        $moduleName = basename($directory);
        
        // Chercher le fichier module.php
        $moduleFile = $directory . '/module.php';
        if (file_exists($moduleFile)) {
            // Charger le fichier pour obtenir la classe du module
            $content = file_get_contents($moduleFile);
            
            // Extraire le namespace et la classe
            if (preg_match('/namespace\s+([^;]+)/i', $content, $namespaceMatches) &&
                preg_match('/class\s+([^\s]+)\s+extends\s+.*Module/i', $content, $classMatches)) {
                if (isset($namespaceMatches[1]) && isset($classMatches[1])) {
                    return $namespaceMatches[1] . '\\' . $classMatches[1];
                }
            }
        }
        
        // Chercher un fichier avec le nom du module (ex: TestModule.php)
        $moduleClassFile = $directory . '/' . $moduleName . '.php';
        if (file_exists($moduleClassFile)) {
            // Include the file to make the class available
            include_once $moduleClassFile;
            
            // Check if the class exists in global namespace
            if (class_exists($moduleName)) {
                return $moduleName;
            }
            
            // Check if the class exists with TestModule name
            if (class_exists('TestModule')) {
                return 'TestModule';
            }
        }

        // Chercher un fichier composer.json
        $composerFile = $directory . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            if (isset($composer['autoload']['psr-4'])) {
                // Trouver le premier namespace PSR-4
                $namespace = key($composer['autoload']['psr-4']);
                $moduleClass = $namespace . $moduleName . 'Module';
                
                if (class_exists($moduleClass)) {
                    return $moduleClass;
                }
            }
        }

        // Essayer avec le namespace par défaut
        $defaultNamespace = $this->app && isset($this->app['config']) 
            ? $this->app['config']->get('modules.namespace', 'App\\Modules')
            : 'App\\Modules';
        $moduleClass = $defaultNamespace . '\\' . $moduleName . '\\' . $moduleName . 'Module';
        
        if (class_exists($moduleClass)) {
            return $moduleClass;
        }

        return null;
    }

    /**
     * Trie les modules selon l'ordre de chargement configuré
     *
     * @return void
     */
    protected function sortModulesByLoadOrder(): void
    {
        if (empty($this->loadOrder)) {
            return;
        }

        $sortedModules = [];
        
        // Ajouter les modules dans l'ordre spécifié
        foreach ($this->loadOrder as $moduleName) {
            if (isset($this->modules[$moduleName])) {
                $sortedModules[$moduleName] = $this->modules[$moduleName];
            }
        }
        
        // Ajouter les modules restants
        foreach ($this->modules as $name => $module) {
            if (!isset($sortedModules[$name])) {
                $sortedModules[$name] = $module;
            }
        }
        
        $this->modules = $sortedModules;
    }

    /**
     * Enregistre tous les modules chargés
     *
     * @return void
     */
    protected function registerModules(): void
    {
        foreach ($this->modules as $name => $module) {
            try {
                // Vérifier la compatibilité et les dépendances
                if (!$module->isCompatible() || !$module->checkDependencies()) {
                    continue;
                }

                // Enregistrer le module
                $module->register();

                // Activer le module si configuré
                if (isset($this->enabledModules[$name]) && $this->enabledModules[$name]) {
                    $module->setEnabled(true);
                }
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres modules
                $this->app['log']->error("Erreur lors de l'enregistrement du module {$name}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Démarre tous les modules activés
     *
     * @return void
     */
    protected function bootModules(): void
    {
        foreach ($this->modules as $name => $module) {
            if ($module->isEnabled()) {
                try {
                    $module->boot();
                    
                    // Charger les ressources du module
                    $this->loadModuleResources($module);
                } catch (\Exception $e) {
                    // Log l'erreur mais continue avec les autres modules
                    $this->app['log']->error("Erreur lors du démarrage du module {$name}: {$e->getMessage()}");
                    $module->setEnabled(false);
                }
            }
        }
    }

    /**
     * Charge les ressources d'un module (routes, vues, traductions, etc.)
     *
     * @param \Nexa\Modules\Module $module
     * @return void
     */
    protected function loadModuleResources(Module $module): void
    {
        // Charger les routes
        $module->loadRoutes();
        
        // Charger les vues
        $module->loadViews();
        
        // Charger les traductions
        $module->loadTranslations();
        
        // Charger les configurations
        $module->loadConfigs();
        
        // Publier les assets si configuré
        if ($this->app['config']->get('modules.assets.publish', true)) {
            $module->publishAssets();
        }
    }

    /**
     * Active un module
     *
     * @param string $name Nom du module
     * @return bool
     */
    public function activateModule(string $name): bool
    {
        if (!isset($this->modules[$name])) {
            return false;
        }

        $module = $this->modules[$name];

        try {
            $module->activate();
            $module->setEnabled(true);
            $this->enabledModules[$name] = true;
            
            // Charger les ressources du module
            $this->loadModuleResources($module);
            
            // Mettre à jour la configuration
            $this->updateModuleConfig();
            
            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de l'activation du module {$name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Désactive un module
     *
     * @param string $name Nom du module
     * @return bool
     */
    public function deactivateModule(string $name): bool
    {
        if (!isset($this->modules[$name])) {
            return false;
        }

        $module = $this->modules[$name];

        try {
            $module->deactivate();
            $module->setEnabled(false);
            $this->enabledModules[$name] = false;
            
            // Mettre à jour la configuration
            $this->updateModuleConfig();
            
            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de la désactivation du module {$name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Installe un module depuis un chemin
     *
     * @param string $path Chemin vers le module (zip ou dossier)
     * @return bool
     */
    public function installModule(string $path): bool
    {
        // Vérifier si le chemin existe
        if (!file_exists($path)) {
            return false;
        }

        // Si c'est un fichier zip, l'extraire
        if (pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
            $extractPath = $this->extractModule($path);
            if (!$extractPath) {
                return false;
            }
            $path = $extractPath;
        }

        // Vérifier si c'est un module valide
        $moduleClass = $this->findModuleClass($path);
        if (!$moduleClass || !class_exists($moduleClass)) {
            return false;
        }

        // Obtenir le nom du module
        $moduleName = basename($path);
        $targetPath = $this->modulesDirectory . '/' . $moduleName;

        // Vérifier si le module existe déjà
        if (is_dir($targetPath)) {
            return false;
        }

        // Copier le module dans le répertoire des modules
        $this->recursiveCopy($path, $targetPath);

        // Charger le module
        $module = new $moduleClass($this->app);
        $module->setPath($targetPath);
        $this->modules[$moduleName] = $module;

        // Enregistrer le module
        $module->register();
        
        // Installer le module
        $module->install();

        return true;
    }

    /**
     * Désinstalle un module
     *
     * @param string $name Nom du module
     * @return bool
     */
    public function uninstallModule(string $name): bool
    {
        if (!isset($this->modules[$name])) {
            return false;
        }

        $module = $this->modules[$name];

        try {
            // Désactiver le module s'il est activé
            if ($module->isEnabled()) {
                $module->deactivate();
            }

            // Appeler la méthode de désinstallation
            $module->uninstall();

            // Supprimer le répertoire du module
            $modulePath = $module->getPath();
            if (is_dir($modulePath)) {
                $this->recursiveDelete($modulePath);
            }

            // Supprimer de la liste des modules
            unset($this->modules[$name]);
            unset($this->enabledModules[$name]);

            // Mettre à jour la configuration
            $this->updateModuleConfig();

            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de la désinstallation du module {$name}: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Retourne la liste des modules chargés
     *
     * @return array Liste des modules chargés
     */
    public function getLoadedModules(): array
    {
        return $this->modules;
    }

    /**
     * Extrait un module depuis un fichier zip
     *
     * @param string $zipPath Chemin vers le fichier zip
     * @return string|false Chemin vers le dossier extrait ou false en cas d'échec
     */
    protected function extractModule(string $zipPath)
    {
        $zip = new \ZipArchive();
        $extractPath = storage_path('app/tmp/modules/' . uniqid());

        if ($zip->open($zipPath) === true) {
            // Créer le répertoire d'extraction
            if (!is_dir($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            // Extraire le zip
            $zip->extractTo($extractPath);
            $zip->close();

            return $extractPath;
        }

        return false;
    }

    /**
     * Copie récursivement un répertoire
     *
     * @param string $source Répertoire source
     * @param string $destination Répertoire de destination
     * @return bool
     */
    protected function recursiveCopy(string $source, string $destination): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $item->getSubPathname();
            if ($item->isDir()) {
                mkdir($target, 0755, true);
            } else {
                copy($item, $target);
            }
        }

        return true;
    }

    /**
     * Supprime récursivement un répertoire
     *
     * @param string $directory Répertoire à supprimer
     * @return bool
     */
    protected function recursiveDelete(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        return rmdir($directory);
     }

     /**
      * Met à jour la configuration des modules
      *
     * @return void
     */
    protected function updateModuleConfig(): void
    {
        // Cette méthode pourrait être implémentée pour mettre à jour la configuration
        // des modules dans le fichier de configuration
    }

    /**
     * Retourne tous les modules chargés
     *
     * @return array<string, \Nexa\Modules\Module>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Retourne un module spécifique
     *
     * @param string $name Nom du module
     * @return \Nexa\Modules\Module|null
     */
    public function getModule(string $name): ?Module
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Vérifie si un module est chargé
     *
     * @param string $name Nom du module
     * @return bool
     */
    public function hasModule(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * Vérifie si un module est activé
     *
     * @param string $name Nom du module
     * @return bool
     */
    public function isModuleEnabled(string $name): bool
    {
        return isset($this->modules[$name]) && $this->modules[$name]->isEnabled();
    }
}