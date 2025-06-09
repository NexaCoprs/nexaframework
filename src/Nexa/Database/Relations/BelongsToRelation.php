<?php

namespace Nexa\Database\Relations;

use Nexa\Database\Model;
use Nexa\Database\QueryBuilder;

class BelongsToRelation extends Relation
{
    protected $ownerKey;

    public function __construct(Model $parent, Model $related, $foreignKey, $ownerKey)
    {
        $this->ownerKey = $ownerKey;
        parent::__construct($parent, $related, $foreignKey, $ownerKey);
    }

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

        $this->query->where($this->ownerKey, '=', $this->getForeignKeyValue());
    }

    /**
     * Obtient les résultats de la relation
     *
     * @return Model|null
     */
    public function getResults()
    {
        if (is_null($this->getForeignKeyValue())) {
            return null;
        }

        return $this->query()->where($this->ownerKey, $this->getForeignKeyValue())->first();
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
     * Obtient la valeur de la clé étrangère
     *
     * @return mixed
     */
    protected function getForeignKeyValue()
    {
        return $this->parent->getAttribute($this->foreignKey);
    }

    /**
     * Associe le modèle parent à un modèle donné
     *
     * @param Model $model
     * @return Model
     */
    public function associate(Model $model)
    {
        $this->parent->setAttribute($this->foreignKey, $model->getAttribute($this->ownerKey));
        
        if ($this->parent->exists) {
            $this->parent->save();
        }
        
        return $this->parent;
    }

    /**
     * Dissocie le modèle parent du modèle lié
     *
     * @return Model
     */
    public function dissociate()
    {
        $this->parent->setAttribute($this->foreignKey, null);
        
        if ($this->parent->exists) {
            $this->parent->save();
        }
        
        return $this->parent;
    }

    /**
     * Crée un nouveau modèle lié et l'associe
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes = [])
    {
        $instance = new (get_class($this->related));
        $instance->fill($attributes);
        $instance->save();
        
        $this->associate($instance);
        
        return $instance;
    }

    /**
     * Met à jour le modèle lié
     *
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes)
    {
        $related = $this->getResults();
        
        if ($related) {
            $related->fill($attributes);
            return $related->save();
        }
        
        return false;
    }

    /**
     * Obtient la clé du propriétaire
     *
     * @return string
     */
    public function getOwnerKeyName()
    {
        return $this->ownerKey;
    }
}