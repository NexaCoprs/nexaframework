<?php

namespace Nexa\Database\Relations;

use Nexa\Database\Model;
use Nexa\Database\QueryBuilder;
use PDO;

class BelongsToManyRelation extends Relation
{
    protected $table;
    protected $foreignPivotKey;
    protected $relatedPivotKey;
    protected $pivotColumns = [];

    public function __construct(Model $parent, Model $related, $table, $foreignPivotKey, $relatedPivotKey)
    {
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
        
        parent::__construct($parent, $related, $foreignPivotKey, $parent->primaryKey);
    }

    /**
     * Ajoute les contraintes de la relation à la requête
     *
     * @return void
     */
    public function addConstraints()
    {
        // Les contraintes seront ajoutées dans la méthode getResults
    }

    /**
     * Obtient les résultats de la relation
     *
     * @return array
     */
    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return [];
        }

        $sql = "SELECT {$this->related->table}.*, {$this->table}.* 
                FROM {$this->related->table} 
                INNER JOIN {$this->table} ON {$this->related->table}.{$this->related->primaryKey} = {$this->table}.{$this->relatedPivotKey} 
                WHERE {$this->table}.{$this->foreignPivotKey} = ?";

        $stmt = $this->parent->getConnection()->prepare($sql);
        $stmt->execute([$this->getParentKey()]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new (get_class($this->related));
            
            // Séparer les données du modèle et du pivot
            $modelData = [];
            $pivotData = [];
            
            foreach ($row as $key => $value) {
                if (strpos($key, $this->table . '_') === 0) {
                    $pivotData[substr($key, strlen($this->table) + 1)] = $value;
                } else {
                    $modelData[$key] = $value;
                }
            }
            
            $model->fill($modelData);
            $model->pivot = $pivotData;
            
            $results[] = $model;
        }

        return $results;
    }

    /**
     * Attache des modèles à la relation
     *
     * @param mixed $id
     * @param array $attributes
     * @return void
     */
    public function attach($id, array $attributes = [])
    {
        $ids = is_array($id) ? $id : [$id];
        
        foreach ($ids as $relatedId) {
            $pivotData = array_merge([
                $this->foreignPivotKey => $this->getParentKey(),
                $this->relatedPivotKey => $relatedId
            ], $attributes);
            
            $this->insertPivot($pivotData);
        }
    }

    /**
     * Détache des modèles de la relation
     *
     * @param mixed $ids
     * @return int
     */
    public function detach($ids = null)
    {
        if (is_null($ids)) {
            return $this->detachAll();
        }
        
        $ids = is_array($ids) ? $ids : [$ids];
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM {$this->table} 
                WHERE {$this->foreignPivotKey} = ? 
                AND {$this->relatedPivotKey} IN ({$placeholders})";
        
        $bindings = array_merge([$this->getParentKey()], $ids);
        $stmt = $this->parent->getConnection()->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->rowCount();
    }

    /**
     * Détache tous les modèles de la relation
     *
     * @return int
     */
    public function detachAll()
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->foreignPivotKey} = ?";
        $stmt = $this->parent->getConnection()->prepare($sql);
        $stmt->execute([$this->getParentKey()]);
        
        return $stmt->rowCount();
    }

    /**
     * Synchronise les IDs avec la relation
     *
     * @param array $ids
     * @param bool $detaching
     * @return array
     */
    public function sync(array $ids, $detaching = true)
    {
        $changes = [
            'attached' => [],
            'detached' => [],
            'updated' => []
        ];
        
        // Obtenir les IDs actuellement attachés
        $current = $this->getCurrentIds();
        
        // Déterminer les changements
        $detach = array_diff($current, array_keys($ids));
        $attach = array_diff(array_keys($ids), $current);
        
        // Détacher si nécessaire
        if ($detaching && !empty($detach)) {
            $this->detach($detach);
            $changes['detached'] = $detach;
        }
        
        // Attacher les nouveaux
        foreach ($attach as $id) {
            $this->attach($id, isset($ids[$id]) ? $ids[$id] : []);
            $changes['attached'][] = $id;
        }
        
        return $changes;
    }

    /**
     * Bascule l'attachement des IDs
     *
     * @param array $ids
     * @return array
     */
    public function toggle(array $ids)
    {
        $changes = [
            'attached' => [],
            'detached' => []
        ];
        
        $current = $this->getCurrentIds();
        
        foreach ($ids as $id => $attributes) {
            if (is_numeric($id)) {
                $id = $attributes;
                $attributes = [];
            }
            
            if (in_array($id, $current)) {
                $this->detach($id);
                $changes['detached'][] = $id;
            } else {
                $this->attach($id, $attributes);
                $changes['attached'][] = $id;
            }
        }
        
        return $changes;
    }

    /**
     * Obtient les IDs actuellement attachés
     *
     * @return array
     */
    protected function getCurrentIds()
    {
        $sql = "SELECT {$this->relatedPivotKey} FROM {$this->table} WHERE {$this->foreignPivotKey} = ?";
        $stmt = $this->parent->getConnection()->prepare($sql);
        $stmt->execute([$this->getParentKey()]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Insère des données dans la table pivot
     *
     * @param array $data
     * @return void
     */
    protected function insertPivot(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->parent->getConnection()->prepare($sql);
        $stmt->execute(array_values($data));
    }

    /**
     * Spécifie les colonnes pivot à récupérer
     *
     * @param array $columns
     * @return $this
     */
    public function withPivot(array $columns)
    {
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);
        return $this;
    }

    /**
     * Obtient le nom de la table pivot
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}