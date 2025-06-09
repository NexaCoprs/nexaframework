<?php

namespace Nexa\Database\Relations;

use Nexa\Database\Model;
use Nexa\Database\QueryBuilder;

class HasOneRelation extends Relation
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
     * @return Model|null
     */
    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return null;
        }

        return $this->query()->where($this->foreignKey, $this->getParentKey())->first();
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
}