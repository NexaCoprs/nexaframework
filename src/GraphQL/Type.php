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
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne les champs du type
     *
     * @return array
     */
    abstract public function getFields(): array;

    /**
     * Retourne les interfaces implémentées
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
     * @param mixed $root Objet racine
     * @param array $args Arguments du champ
     * @param mixed $context Contexte de la requête
     * @param array $info Informations sur le champ
     * @return mixed
     */
    public function resolveField($root, array $args, $context, array $info)
    {
        $fieldName = $info['fieldName'] ?? null;
        
        if (!$fieldName) {
            return null;
        }

        // Chercher une méthode de résolution spécifique
        $resolverMethod = 'resolve' . ucfirst($fieldName);
        if (method_exists($this, $resolverMethod)) {
            return $this->$resolverMethod($root, $args, $context, $info);
        }

        // Résolution par défaut
        if (is_array($root) && isset($root[$fieldName])) {
            return $root[$fieldName];
        }

        if (is_object($root)) {
            // Essayer d'accéder à la propriété
            if (property_exists($root, $fieldName)) {
                return $root->$fieldName;
            }

            // Essayer d'appeler une méthode getter
            $getterMethod = 'get' . ucfirst($fieldName);
            if (method_exists($root, $getterMethod)) {
                return $root->$getterMethod();
            }

            // Essayer d'appeler la méthode directement
            if (method_exists($root, $fieldName)) {
                return $root->$fieldName();
            }
        }

        return null;
    }

    /**
     * Valide les arguments d'un champ
     *
     * @param array $args Arguments à valider
     * @param array $fieldDefinition Définition du champ
     * @return bool|string True si valide, message d'erreur sinon
     */
    protected function validateArgs(array $args, array $fieldDefinition)
    {
        $argDefinitions = $fieldDefinition['args'] ?? [];

        foreach ($argDefinitions as $argName => $argDef) {
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
    protected function validateArgType($value, string $expectedType)
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
     * Applique la pagination aux résultats
     *
     * @param array $items Éléments à paginer
     * @param array $args Arguments de pagination
     * @return array Résultats paginés
     */
    protected function paginate(array $items, array $args): array
    {
        $first = $args['first'] ?? null;
        $after = $args['after'] ?? null;
        $last = $args['last'] ?? null;
        $before = $args['before'] ?? null;

        $totalCount = count($items);
        $startIndex = 0;
        $endIndex = $totalCount;

        // Appliquer la pagination "first" et "after"
        if ($after !== null) {
            $afterIndex = array_search($after, array_keys($items));
            if ($afterIndex !== false) {
                $startIndex = $afterIndex + 1;
            }
        }

        if ($first !== null) {
            $endIndex = min($startIndex + $first, $totalCount);
        }

        // Appliquer la pagination "last" et "before"
        if ($before !== null) {
            $beforeIndex = array_search($before, array_keys($items));
            if ($beforeIndex !== false) {
                $endIndex = min($beforeIndex, $endIndex);
            }
        }

        if ($last !== null) {
            $startIndex = max($endIndex - $last, $startIndex);
        }

        $paginatedItems = array_slice($items, $startIndex, $endIndex - $startIndex, true);
        $edges = [];
        
        foreach ($paginatedItems as $key => $item) {
            $edges[] = [
                'node' => $item,
                'cursor' => (string) $key
            ];
        }

        return [
            'edges' => $edges,
            'pageInfo' => [
                'hasNextPage' => $endIndex < $totalCount,
                'hasPreviousPage' => $startIndex > 0,
                'startCursor' => !empty($edges) ? $edges[0]['cursor'] : null,
                'endCursor' => !empty($edges) ? end($edges)['cursor'] : null
            ],
            'totalCount' => $totalCount
        ];
    }

    /**
     * Filtre les résultats selon les critères spécifiés
     *
     * @param array $items Éléments à filtrer
     * @param array $filters Critères de filtrage
     * @return array Résultats filtrés
     */
    protected function filter(array $items, array $filters): array
    {
        if (empty($filters)) {
            return $items;
        }

        return array_filter($items, function ($item) use ($filters) {
            foreach ($filters as $field => $value) {
                $itemValue = null;
                
                if (is_array($item) && isset($item[$field])) {
                    $itemValue = $item[$field];
                } elseif (is_object($item) && property_exists($item, $field)) {
                    $itemValue = $item->$field;
                } elseif (is_object($item) && method_exists($item, 'get' . ucfirst($field))) {
                    $getterMethod = 'get' . ucfirst($field);
                    $itemValue = $item->$getterMethod();
                }

                if ($itemValue !== $value) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Trie les résultats selon les critères spécifiés
     *
     * @param array $items Éléments à trier
     * @param array $sort Critères de tri
     * @return array Résultats triés
     */
    protected function sort(array $items, array $sort): array
    {
        if (empty($sort)) {
            return $items;
        }

        usort($items, function ($a, $b) use ($sort) {
            foreach ($sort as $field => $direction) {
                $aValue = $this->getFieldValue($a, $field);
                $bValue = $this->getFieldValue($b, $field);

                $comparison = $aValue <=> $bValue;
                
                if ($comparison !== 0) {
                    return strtolower($direction) === 'desc' ? -$comparison : $comparison;
                }
            }
            
            return 0;
        });

        return $items;
    }

    /**
     * Extrait la valeur d'un champ depuis un élément
     *
     * @param mixed $item Élément
     * @param string $field Nom du champ
     * @return mixed Valeur du champ
     */
    protected function getFieldValue($item, string $field)
    {
        if (is_array($item) && isset($item[$field])) {
            return $item[$field];
        }

        if (is_object($item)) {
            if (property_exists($item, $field)) {
                return $item->$field;
            }

            $getterMethod = 'get' . ucfirst($field);
            if (method_exists($item, $getterMethod)) {
                return $item->$getterMethod();
            }
        }

        return null;
    }
}