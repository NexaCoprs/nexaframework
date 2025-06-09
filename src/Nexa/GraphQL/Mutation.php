<?php

namespace Nexa\GraphQL;

/**
 * Classe de base pour les mutations GraphQL
 * 
 * Cette classe abstraite définit l'interface de base pour toutes les mutations GraphQL
 * dans le framework Nexa.
 * 
 * @package Nexa\GraphQL
 */
abstract class Mutation
{
    /**
     * Nom de la mutation GraphQL
     *
     * @var string
     */
    protected $name;

    /**
     * Description de la mutation GraphQL
     *
     * @var string
     */
    protected $description;

    /**
     * Type de retour de la mutation
     *
     * @var string|array
     */
    protected $type;

    /**
     * Arguments de la mutation
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
     * Retourne le nom de la mutation
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne la description de la mutation
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * Retourne le type de retour de la mutation
     *
     * @return string|array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Retourne les arguments de la mutation
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Résout la mutation
     *
     * Cette méthode doit être implémentée par les classes enfants pour
     * définir la logique de résolution de la mutation.
     *
     * @param mixed $root Objet racine
     * @param array $args Arguments de la mutation
     * @param mixed $context Contexte de la mutation
     * @param array $info Informations sur la mutation
     * @return mixed
     */
    abstract public function resolve($root, array $args, $context, array $info);
}