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

    /**
     * Valide les arguments de la mutation
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
     * Applique l'autorisation pour la mutation
     *
     * @param mixed $context Contexte de la mutation
     * @return bool True si autorisé, false sinon
     */
    protected function authorize($context): bool
    {
        // Par défaut, toutes les mutations nécessitent une authentification
        // Les classes enfants peuvent surcharger cette méthode pour implémenter
        // une logique d'autorisation spécifique
        return isset($context['user']) && $context['user'] !== null;
    }

    /**
     * Applique le middleware avant la résolution de la mutation
     *
     * @param mixed $root Objet racine
     * @param array $args Arguments de la mutation
     * @param mixed $context Contexte de la mutation
     * @param array $info Informations sur la mutation
     * @return mixed
     */
    public function applyMiddleware($root, array $args, $context, array $info)
    {
        // Vérifier l'autorisation
        if (!$this->authorize($context)) {
            throw new \Exception('Unauthorized access to mutation: ' . $this->name);
        }

        // Valider les arguments
        $validation = $this->validateArgs($args);
        if ($validation !== true) {
            throw new \Exception($validation);
        }

        // Appliquer des middlewares supplémentaires si nécessaire
        // ...

        // Résoudre la mutation
        return $this->resolve($root, $args, $context, $info);
    }

    /**
     * Convertit la mutation en définition GraphQL
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

    /**
     * Exécute des validations métier spécifiques avant la mutation
     *
     * @param array $args Arguments de la mutation
     * @param mixed $context Contexte de la mutation
     * @return bool|string True si valide, message d'erreur sinon
     */
    protected function validateBusiness(array $args, $context): bool|string
    {
        // Par défaut, pas de validation métier supplémentaire
        // Les classes enfants peuvent surcharger cette méthode pour implémenter
        // une logique de validation métier spécifique
        return true;
    }

    /**
     * Enregistre l'événement de mutation dans les logs
     *
     * @param array $args Arguments de la mutation
     * @param mixed $context Contexte de la mutation
     * @param mixed $result Résultat de la mutation
     * @return void
     */
    protected function logMutation(array $args, $context, $result): void
    {
        // Implémentation de base du logging
        // Les classes enfants peuvent surcharger cette méthode pour implémenter
        // une logique de logging spécifique
        if (isset($context['logger'])) {
            $userId = isset($context['user']) ? $context['user']->getId() : 'anonymous';
            $context['logger']->info("Mutation {$this->name} executed by user {$userId}", [
                'mutation' => $this->name,
                'user_id' => $userId,
                'args' => $args,
                'result' => $result
            ]);
        }
    }

    /**
     * Déclenche des événements après la mutation
     *
     * @param array $args Arguments de la mutation
     * @param mixed $context Contexte de la mutation
     * @param mixed $result Résultat de la mutation
     * @return void
     */
    protected function fireEvents(array $args, $context, $result): void
    {
        // Implémentation de base des événements
        // Les classes enfants peuvent surcharger cette méthode pour implémenter
        // une logique d'événements spécifique
        if (isset($context['events'])) {
            $context['events']->dispatch("mutation.{$this->name}.completed", [
                'mutation' => $this->name,
                'args' => $args,
                'result' => $result,
                'user' => $context['user'] ?? null
            ]);
        }
    }
}