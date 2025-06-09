<?php

namespace Nexa\Plugins;

use Nexa\Core\Application;
use Nexa\Contracts\Container\Container;

/**
 * Gestionnaire de plugins pour Nexa Framework
 * 
 * Cette classe est responsable du chargement, de l'activation et de la gestion
 * des plugins dans le framework Nexa.
 * 
 * @package Nexa\Plugins
 */
class PluginManager
{
    /**
     * Instance de l'application Nexa
     *
     * @var \Nexa\Core\Application
     */
    protected $app;



    /**
     * Répertoire des plugins
     *
     * @var string
     */
    protected $pluginsDirectory;

    /**
     * Liste des plugins chargés
     *
     * @var array<string, \Nexa\Plugins\Plugin>
     */
    protected $plugins = [];

    /**
     * Liste des plugins activés
     *
     * @var array<string, bool>
     */
    protected $enabledPlugins = [];

    /**
     * Indique si les plugins ont été chargés
     *
     * @var bool
     */
    protected $loaded = false;

    /**
     * Constructeur
     *
     * @param \Nexa\Core\Application $app Instance de l'application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->pluginsDirectory = \Nexa\Core\Config::get('plugins.directory', base_path('plugins'));
        $this->enabledPlugins = \Nexa\Core\Config::get('plugins.plugins', []);
    }

    /**
     * Charge tous les plugins disponibles
     *
     * @return void
     */
    public function loadPlugins(): void
    {
        if ($this->loaded) {
            return;
        }

        // Vérifier si le système de plugins est activé
        if (!\Nexa\Core\Config::get('plugins.enabled', true)) {
            return;
        }

        // Vérifier si le répertoire des plugins existe
        if (!is_dir($this->pluginsDirectory)) {
            mkdir($this->pluginsDirectory, 0755, true);
        }

        // Charger les plugins depuis le répertoire
        if (\Nexa\Core\Config::get('plugins.auto_discover', true) && empty($this->plugins)) {
            $this->discoverPlugins();
        }

        // Enregistrer les plugins
        $this->registerPlugins();

        // Démarrer les plugins activés
        $this->bootPlugins();

        $this->loaded = true;
    }

    /**
     * Découvre les plugins disponibles dans le répertoire des plugins
     *
     * @return void
     */
    protected function discoverPlugins(): void
    {
        $directories = glob($this->pluginsDirectory . '/*', GLOB_ONLYDIR);

        foreach ($directories as $directory) {
            $pluginName = basename($directory);
            $pluginClass = $this->findPluginClass($directory);

            if ($pluginClass && class_exists($pluginClass)) {
                $this->plugins[$pluginName] = new $pluginClass($this->app);
            }
        }
    }

    /**
     * Trouve la classe principale du plugin dans le répertoire spécifié
     *
     * @param string $directory Répertoire du plugin
     * @return string|null Nom de la classe du plugin ou null si non trouvé
     */
    protected function findPluginClass(string $directory): ?string
    {
        // Chercher le fichier plugin.php
        $pluginFile = $directory . '/plugin.php';
        if (file_exists($pluginFile)) {
            // Charger le fichier pour obtenir la classe du plugin
            $content = file_get_contents($pluginFile);
            
            // Extraire le namespace et la classe
            if (preg_match('/namespace\s+([^;]+)/i', $content, $namespaceMatches) &&
                preg_match('/class\s+([^\s]+)\s+extends\s+.*Plugin/i', $content, $classMatches)) {
                return $namespaceMatches[1] . '\\' . $classMatches[1];
            }
        }

        // Chercher un fichier composer.json
        $composerFile = $directory . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            if (isset($composer['autoload']['psr-4'])) {
                // Trouver le premier namespace PSR-4
                $namespace = key($composer['autoload']['psr-4']);
                $pluginClass = $namespace . 'Plugin';
                
                if (class_exists($pluginClass)) {
                    return $pluginClass;
                }
            }
        }

        return null;
    }

    /**
     * Enregistre tous les plugins chargés
     *
     * @return void
     */
    protected function registerPlugins(): void
    {
        foreach ($this->plugins as $name => $plugin) {
            try {
                // Vérifier la compatibilité et les dépendances
                if (!$plugin->isCompatible() || !$plugin->checkDependencies()) {
                    continue;
                }

                // Enregistrer le plugin
                $plugin->register();

                // Activer le plugin si configuré
                if (isset($this->enabledPlugins[$name]) && $this->enabledPlugins[$name]) {
                    $plugin->setEnabled(true);
                }
            } catch (\Exception $e) {
                // Log l'erreur mais continue avec les autres plugins
                $this->app['log']->error("Erreur lors de l'enregistrement du plugin {$name}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Démarre tous les plugins activés
     *
     * @return void
     */
    protected function bootPlugins(): void
    {
        foreach ($this->plugins as $name => $plugin) {
            if ($plugin->isEnabled()) {
                try {
                    $plugin->boot();
                } catch (\Exception $e) {
                    // Log l'erreur mais continue avec les autres plugins
                    $this->app['log']->error("Erreur lors du démarrage du plugin {$name}: {$e->getMessage()}");
                    $plugin->setEnabled(false);
                }
            }
        }
    }

    /**
     * Active un plugin
     *
     * @param string $name Nom du plugin
     * @return bool
     */
    public function activatePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $plugin = $this->plugins[$name];

        try {
            $plugin->activate();
            $plugin->setEnabled(true);
            $this->enabledPlugins[$name] = true;
            
            // Mettre à jour la configuration
            $this->updatePluginConfig();
            
            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de l'activation du plugin {$name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Désactive un plugin
     *
     * @param string $name Nom du plugin
     * @return bool
     */
    public function deactivatePlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $plugin = $this->plugins[$name];

        try {
            $plugin->deactivate();
            $plugin->setEnabled(false);
            $this->enabledPlugins[$name] = false;
            
            // Mettre à jour la configuration
            $this->updatePluginConfig();
            
            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de la désactivation du plugin {$name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Installe un plugin depuis un chemin
     *
     * @param string $path Chemin vers le plugin (zip ou dossier)
     * @return bool
     */
    public function installPlugin(string $path): bool
    {
        // Vérifier si le chemin existe
        if (!file_exists($path)) {
            return false;
        }

        // Si c'est un fichier zip, l'extraire
        if (pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
            $extractPath = $this->extractPlugin($path);
            if (!$extractPath) {
                return false;
            }
            $path = $extractPath;
        }

        // Vérifier si c'est un plugin valide
        $pluginClass = $this->findPluginClass($path);
        if (!$pluginClass || !class_exists($pluginClass)) {
            return false;
        }

        // Obtenir le nom du plugin
        $pluginName = basename($path);
        $targetPath = $this->pluginsDirectory . '/' . $pluginName;

        // Vérifier si le plugin existe déjà
        if (is_dir($targetPath)) {
            return false;
        }

        // Copier le plugin dans le répertoire des plugins
        $this->recursiveCopy($path, $targetPath);

        // Charger le plugin
        $plugin = new $pluginClass($this->app);
        $this->plugins[$pluginName] = $plugin;

        // Enregistrer le plugin
        $plugin->register();

        return true;
    }

    /**
     * Désinstalle un plugin
     *
     * @param string $name Nom du plugin
     * @return bool
     */
    public function uninstallPlugin(string $name): bool
    {
        if (!isset($this->plugins[$name])) {
            return false;
        }

        $plugin = $this->plugins[$name];

        try {
            // Désactiver le plugin s'il est activé
            if ($plugin->isEnabled()) {
                $plugin->deactivate();
            }

            // Appeler la méthode de désinstallation
            $plugin->uninstall();

            // Supprimer le répertoire du plugin
            $pluginPath = $this->pluginsDirectory . '/' . $name;
            if (is_dir($pluginPath)) {
                $this->recursiveDelete($pluginPath);
            }

            // Supprimer de la liste des plugins
            unset($this->plugins[$name]);
            unset($this->enabledPlugins[$name]);

            // Mettre à jour la configuration
            $this->updatePluginConfig();

            return true;
        } catch (\Exception $e) {
            $this->app['log']->error("Erreur lors de la désinstallation du plugin {$name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Extrait un plugin depuis un fichier zip
     *
     * @param string $zipPath Chemin vers le fichier zip
     * @return string|false Chemin vers le dossier extrait ou false en cas d'échec
     */
    protected function extractPlugin(string $zipPath)
    {
        $zip = new \ZipArchive();
        $extractPath = storage_path('app/tmp/plugins/' . uniqid());

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
     * Met à jour la configuration des plugins
     *
     * @return void
     */
    protected function updatePluginConfig(): void
    {
        // Cette méthode pourrait être implémentée pour mettre à jour la configuration
        // des plugins dans le fichier de configuration
    }

    /**
     * Retourne tous les plugins chargés
     *
     * @return array<string, \Nexa\Plugins\Plugin>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Retourne un plugin spécifique
     *
     * @param string $name Nom du plugin
     * @return \Nexa\Plugins\Plugin|null
     */
    public function getPlugin(string $name): ?Plugin
    {
        return $this->plugins[$name] ?? null;
    }

    /**
     * Vérifie si un plugin est chargé
     *
     * @param string $name Nom du plugin
     * @return bool
     */
    public function hasPlugin(string $name): bool
    {
        return isset($this->plugins[$name]);
    }

    /**
     * Vérifie si un plugin est activé
     *
     * @param string $name Nom du plugin
     * @return bool
     */
    public function isPluginEnabled(string $name): bool
    {
        return isset($this->plugins[$name]) && $this->plugins[$name]->isEnabled();
    }

    /**
     * Retourne tous les plugins activés
     *
     * @return array<string, Plugin>
     */
    public function getActivePlugins(): array
    {
        return array_filter($this->plugins, function($plugin) {
            return $plugin->isEnabled();
        });
    }

    /**
     * Met à jour un plugin
     *
     * @param string $name Nom du plugin
     * @param string $version Nouvelle version
     * @return bool
     */
    public function updatePlugin(string $name, string $version): bool
    {
        if (!$this->hasPlugin($name)) {
            return false;
        }

        $plugin = $this->getPlugin($name);
        $oldVersion = $plugin->getVersion();
        
        // Simulate update process
        if (method_exists($plugin, 'update')) {
            $plugin->update($oldVersion, $version);
            return true;
        }
        
        return true;
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
     * Retourne la liste des plugins chargés
     *
     * @return array<string, \Nexa\Plugins\Plugin>
     */
    public function getLoadedPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Découvre les plugins dans un répertoire spécifique (version publique pour les tests)
     *
     * @param string $directory Répertoire à scanner
     * @return array<string, string> Liste des plugins découverts
     */
    public function scanPlugins(string $directory = null): array
    {
        $searchDirectory = $directory ?? $this->pluginsDirectory;
        $discoveredPlugins = [];
        
        if (!is_dir($searchDirectory)) {
            return $discoveredPlugins;
        }

        $directories = glob($searchDirectory . '/*', GLOB_ONLYDIR);
        if (!$directories) {
            // Si pas de sous-dossiers, chercher les fichiers PHP directement
            $files = glob($searchDirectory . '/*.php');
            foreach ($files as $file) {
                $pluginName = basename($file, '.php');
                $discoveredPlugins[$pluginName] = $file;
                
                // Charger le fichier et instancier le plugin
                require_once $file;
                $className = $pluginName;
                if (class_exists($className)) {
                    $plugin = new $className($this->app);
                    // Store plugin with its actual class name
                $this->plugins[$className] = $plugin;
                }
            }
            return $discoveredPlugins;
        }

        foreach ($directories as $dir) {
            $pluginName = basename($dir);
            $pluginClass = $this->findPluginClass($dir);

            if ($pluginClass) {
                $discoveredPlugins[$pluginName] = $pluginClass;
                
                // Instancier le plugin et l'ajouter à la collection
                if (class_exists($pluginClass)) {
                    $this->plugins[$pluginName] = new $pluginClass($this->app);
                }
            }
        }

        return $discoveredPlugins;
    }
}