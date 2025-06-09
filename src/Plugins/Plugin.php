<?php

namespace Nexa\Plugins;

/**
 * Classe de base pour tous les plugins Nexa
 * 
 * Cette classe abstraite définit l'interface et les fonctionnalités de base
 * que tous les plugins Nexa doivent implémenter.
 * 
 * @package Nexa\Plugins
 */
abstract class Plugin
{
    /**
     * Instance de l'application Nexa
     *
     * @var \Nexa\Core\Application
     */
    protected $app;

    /**
     * Nom du plugin
     *
     * @var string
     */
    protected $name;

    /**
     * Version du plugin
     *
     * @var string
     */
    protected $version;

    /**
     * Description du plugin
     *
     * @var string
     */
    protected $description;

    /**
     * Auteur du plugin
     *
     * @var string
     */
    protected $author;

    /**
     * URL du plugin
     *
     * @var string
     */
    protected $url;

    /**
     * Dépendances du plugin
     *
     * @var array
     */
    protected $dependencies = [];

    /**
     * Indique si le plugin est activé
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Constructeur
     *
     * @param \Nexa\Core\Application $app Instance de l'application
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Méthode appelée lors de l'enregistrement du plugin
     * 
     * Cette méthode est appelée une seule fois lors du chargement initial du plugin.
     * Utilisez cette méthode pour enregistrer des liaisons dans le conteneur,
     * des fournisseurs de services, etc.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Méthode appelée lors du démarrage du plugin
     * 
     * Cette méthode est appelée après que tous les plugins ont été enregistrés.
     * Utilisez cette méthode pour effectuer des actions qui dépendent d'autres plugins.
     *
     * @return void
     */
    abstract public function boot(): void;

    /**
     * Méthode appelée lors de l'activation du plugin
     * 
     * Cette méthode est appelée lorsque le plugin est activé par l'utilisateur.
     * Utilisez cette méthode pour effectuer des actions d'initialisation comme
     * la création de tables en base de données, etc.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->enabled = true;
    }

    /**
     * Méthode appelée lors de la désactivation du plugin
     * 
     * Cette méthode est appelée lorsque le plugin est désactivé par l'utilisateur.
     * Utilisez cette méthode pour effectuer des actions de nettoyage.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->enabled = false;
    }

    /**
     * Méthode appelée lors de la désinstallation du plugin
     * 
     * Cette méthode est appelée lorsque le plugin est désinstallé par l'utilisateur.
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
     * Méthode appelée lors de la mise à jour du plugin
     * 
     * Cette méthode est appelée lorsque le plugin est mis à jour vers une nouvelle version.
     * Utilisez cette méthode pour effectuer des migrations de données, etc.
     *
     * @param string $oldVersion Ancienne version du plugin
     * @param string $newVersion Nouvelle version du plugin
     * @return void
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        $this->version = $newVersion;
    }

    /**
     * Vérifie si le plugin est compatible avec la version actuelle du framework
     *
     * @return bool
     */
    public function isCompatible(): bool
    {
        // Par défaut, on considère que le plugin est compatible
        return true;
    }

    /**
     * Vérifie si toutes les dépendances du plugin sont satisfaites
     *
     * @return bool
     */
    public function checkDependencies(): bool
    {
        // Vérification à implémenter par le gestionnaire de plugins
        return true;
    }

    /**
     * Retourne le nom du plugin
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne la version du plugin
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Retourne la description du plugin
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne l'auteur du plugin
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Retourne l'URL du plugin
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Retourne les dépendances du plugin
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * Vérifie si le plugin est activé
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Définit si le plugin est activé
     *
     * @param bool $enabled
     * @return void
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}