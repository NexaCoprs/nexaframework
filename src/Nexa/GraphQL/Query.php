<?php

namespace Nexa\GraphQL;

/**
 * Classe de base pour les requêtes GraphQL
 * 
 * Cette classe abstraite définit l'interface de base pour toutes les requêtes GraphQL
 * dans le framework Nexa.
 * 
 * @package Nexa\GraphQL
 */
abstract class Query
{
    /**
     * Nom de la requête GraphQL
     *
     * @var string
     */
    protected $name;

    /**
     * Description de la requête GraphQL
     *
     * @var string
     */
    protected $description;

    /**
     * Type de retour de la requête
     *
     * @var string|array
     */
    protected $type;

    /**
     * Arguments de la requête
     *
     * @var array
     */
    protected $args = [];

    /**
     * Constructeur
     */
    public function __construct()
    {
        if (empty($this->name)) {
            // Utiliser le nom de la classe comme nom par défaut
            $className = get_class($this);
            $parts = explode('\\', $className);
            $this->name = lcfirst(end($parts));
        }
    }

    /**
     * Retourne le nom de la requête
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne la description de la requête
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Retourne le type de retour de la requête
     *
     * @return string|array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Retourne les arguments de la requête
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Résout la requête
     *
     * Cette méthode doit être implémentée par les classes enfants pour
     * définir la logique de résolution de la requête.
     *
     * @param mixed $root Objet racine
     * @param array $args Arguments de la requête
     * @param mixed $context Contexte de la requête
     * @param array $info Informations sur la requête
     * @return mixed
     */
    abstract public function resolve($root, array $args, $context, array $info);
}