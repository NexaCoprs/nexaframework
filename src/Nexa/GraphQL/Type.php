<?php

namespace Nexa\GraphQL;

/**
 * Classe de base pour les types GraphQL
 * 
 * Cette classe abstraite définit l'interface de base pour tous les types GraphQL
 * dans le framework Nexa.
 * 
 * @package Nexa\GraphQL
 */
abstract class Type
{
    /**
     * Nom du type GraphQL
     *
     * @var string
     */
    protected $name;

    /**
     * Description du type GraphQL
     *
     * @var string
     */
    protected $description;

    /**
     * Champs du type GraphQL
     *
     * @var array
     */
    protected $fields = [];

    /**
     * Interfaces implémentées par ce type
     *
     * @var array
     */
    protected $interfaces = [];

    /**
     * Retourne le nom du type
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retourne la description du type
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Retourne les champs du type
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Retourne les interfaces implémentées par ce type
     *
     * @return array
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * Résout un champ du type
     *
     * @param string $fieldName Nom du champ à résoudre
     * @param array $args Arguments du champ
     * @param mixed $context Contexte d'exécution
     * @return mixed
     */
    abstract public function resolveField(string $fieldName, array $args = [], $context = null);
}