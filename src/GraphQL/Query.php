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

    /**
     * Valide les arguments de la requête
     *
     * @param array $args Arguments à valider
     * @return bool|string True si valide, message d'erreur sinon
     */
    protected function validateArgs(array $args): bool|string
    {
        foreach ($this->args as $argName => $argDef) {
            // Vérifier les arguments requis
            if (($argDef['required'] ?? false) && !isset($args[$argName])) {
                return "Required argument '{$argName}' is missing";
            }

            // Vérifier les types d'arguments
            if (isset($args[$argName]) && isset($argDef['type'])) {
                $valid = $this->validateArgType($args[$argName], $argDef['type']);
                if ($valid !== true) {
                    return "Invalid type for argument '{$argName}': {$valid}";
                }
            }
        }

        return true;
    }

    /**
     * Valide le type d'un argument
     *
     * @param mixed $value Valeur à valider
     * @param string $expectedType Type attendu
     * @return bool|string True si valide, message d'erreur sinon
     */
    protected function validateArgType($value, string $expectedType): bool|string
    {
        switch (strtolower($expectedType)) {
            case 'string':
                return is_string($value) ? true : 'Expected string';
            case 'int':
            case 'integer':
                return is_int($value) ? true : 'Expected integer';
            case 'float':
                return is_float($value) ? true : 'Expected float';
            case 'bool':
            case 'boolean':
                return is_bool($value) ? true : 'Expected boolean';
            case 'array':
                return is_array($value) ? true : 'Expected array';
            default:
                return true; // Type personnalisé, validation à implémenter
        }
    }

    /**
     * Applique l'autorisation pour la requête
     *
     * @param mixed $context Contexte de la requête
     * @return bool True si autorisé, false sinon
     */
    protected function authorize($context): bool
    {
        // Par défaut, toutes les requêtes sont autorisées
        // Les classes enfants peuvent surcharger cette méthode pour implémenter
        // une logique d'autorisation spécifique
        return true;
    }

    /**
     * Applique le middleware avant la résolution de la requête
     *
     * @param mixed $root Objet racine
     * @param array $args Arguments de la requête
     * @param mixed $context Contexte de la requête
     * @param array $info Informations sur la requête
     * @return mixed
     */
    public function applyMiddleware($root, array $args, $context, array $info)
    {
        // Vérifier l'autorisation
        if (!$this->authorize($context)) {
            throw new \Exception('Unauthorized access to query: ' . $this->name);
        }

        // Valider les arguments
        $validation = $this->validateArgs($args);
        if ($validation !== true) {
            throw new \Exception($validation);
        }

        // Appliquer des middlewares supplémentaires si nécessaire
        // ...

        // Résoudre la requête
        return $this->resolve($root, $args, $context, $info);
    }

    /**
     * Convertit la requête en définition GraphQL
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'args' => $this->getArgs(),
            'resolve' => [$this, 'applyMiddleware']
        ];
    }
}