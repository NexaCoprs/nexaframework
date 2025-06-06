<?php

namespace Nexa\Database\Relations;

use Nexa\Database\Model;
use Nexa\Database\QueryBuilder;

class HasManyRelation extends Relation
{
    /**
     * Ajoute les contraintes de la relation à la requête
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$noConstraints) {
            return;
        }

        $this->query->where($this->foreignKey, '=', $this->getParentKey());
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

        return $this->query()->where($this->foreignKey, $this->getParentKey())->get();
    }

    /**
     * Crée un nouveau query builder pour la relation
     *
     * @return QueryBuilder
     */
    protected function query()
    {
        return new QueryBuilder($this->related);
    }

    /**
     * Crée un nouveau modèle lié
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes = [])
    {
        $attributes[$this->foreignKey] = $this->getParentKey();
        
        $instance = new (get_class($this->related));
        $instance->fill($attributes);
        $instance->save();
        
        return $instance;
    }

    /**
     * Crée plusieurs nouveaux modèles liés
     *
     * @param array $records
     * @return array
     */
    public function createMany(array $records)
    {
        $instances = [];
        
        foreach ($records as $record) {
            $instances[] = $this->create($record);
        }
        
        return $instances;
    }

    /**
     * Sauvegarde un modèle existant avec la relation
     *
     * @param Model $model
     * @return Model
     */
    public function save(Model $model)
    {
        $model->setAttribute($this->foreignKey, $this->getParentKey());
        $model->save();
        
        return $model;
    }

    /**
     * Sauvegarde plusieurs modèles existants avec la relation
     *
     * @param array $models
     * @return array
     */
    public function saveMany(array $models)
    {
        foreach ($models as $model) {
            $this->save($model);
        }
        
        return $models;
    }

    /**
     * Ajoute une clause WHERE à la relation
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        return $this->query()->where($column, $operator, $value);
    }

    /**
     * Ajoute une clause ORDER BY à la relation
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        return $this->query()->orderBy($column, $direction);
    }

    /**
     * Limite le nombre de résultats
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        return $this->query()->limit($limit);
    }
}