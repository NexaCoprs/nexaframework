<?php

namespace Nexa\Events;

/**
 * Model Creating Event (before save)
 */
class ModelCreating extends Event
{
    public function __construct($model, $attributes = [])
    {
        parent::__construct([
            'model' => $model,
            'attributes' => $attributes,
            'action' => 'creating',
            'model_class' => get_class($model)
        ]);
    }

    public function getModel()
    {
        return $this->get('model');
    }

    public function getAttributes()
    {
        return $this->get('attributes');
    }

    public function getModelClass()
    {
        return $this->get('model_class');
    }
}

/**
 * Model Created Event (after save)
 */
class ModelCreated extends Event
{
    public function __construct($modelName, $modelData)
    {
        parent::__construct([
            'model_name' => $modelName,
            'model_data' => $modelData,
            'action' => 'created',
            'model_id' => is_array($modelData) ? ($modelData['id'] ?? null) : ($modelData->id ?? null)
        ]);
    }

    public function getModel()
    {
        return $this->get('model_data');
    }

    public function getModelClass()
    {
        return $this->get('model_name');
    }

    public function getModelId()
    {
        return $this->get('model_id');
    }

    public function getModelName()
    {
        return $this->get('model_name');
    }

    public function getModelData()
    {
        return $this->get('model_data');
    }
}

/**
 * Model Updating Event (before update)
 */
class ModelUpdating extends Event
{
    public function __construct($model, $attributes = [], $originalAttributes = [])
    {
        parent::__construct([
            'model' => $model,
            'attributes' => $attributes,
            'original_attributes' => $originalAttributes,
            'action' => 'updating',
            'model_class' => get_class($model),
            'model_id' => $model->id ?? null
        ]);
    }

    public function getModel()
    {
        return $this->get('model');
    }

    public function getAttributes()
    {
        return $this->get('attributes');
    }

    public function getOriginalAttributes()
    {
        return $this->get('original_attributes');
    }

    public function getModelClass()
    {
        return $this->get('model_class');
    }

    public function getModelId()
    {
        return $this->get('model_id');
    }

    public function getChangedAttributes()
    {
        $attributes = $this->getAttributes();
        $original = $this->getOriginalAttributes();
        $changed = [];

        foreach ($attributes as $key => $value) {
            if (!isset($original[$key]) || $original[$key] !== $value) {
                $changed[$key] = [
                    'old' => $original[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        return $changed;
    }
}

/**
 * Model Updated Event (after update)
 */
class ModelUpdated extends Event
{
    public function __construct($model, $changedAttributes = [])
    {
        parent::__construct([
            'model' => $model,
            'changed_attributes' => $changedAttributes,
            'action' => 'updated',
            'model_class' => get_class($model),
            'model_id' => $model->id ?? null
        ]);
    }

    public function getModel()
    {
        return $this->get('model');
    }

    public function getChangedAttributes()
    {
        return $this->get('changed_attributes');
    }

    public function getModelClass()
    {
        return $this->get('model_class');
    }

    public function getModelId()
    {
        return $this->get('model_id');
    }
}

/**
 * Model Deleting Event (before delete)
 */
class ModelDeleting extends Event
{
    public function __construct($model)
    {
        parent::__construct([
            'model' => $model,
            'action' => 'deleting',
            'model_class' => get_class($model),
            'model_id' => $model->id ?? null
        ]);
    }

    public function getModel()
    {
        return $this->get('model');
    }

    public function getModelClass()
    {
        return $this->get('model_class');
    }

    public function getModelId()
    {
        return $this->get('model_id');
    }
}

/**
 * Model Deleted Event (after delete)
 */
class ModelDeleted extends Event
{
    public function __construct($model, $deletedAttributes = [])
    {
        parent::__construct([
            'model' => $model,
            'deleted_attributes' => $deletedAttributes,
            'action' => 'deleted',
            'model_class' => get_class($model),
            'model_id' => $model->id ?? null
        ]);
    }

    public function getModel()
    {
        return $this->get('model');
    }

    public function getDeletedAttributes()
    {
        return $this->get('deleted_attributes');
    }

    public function getModelClass()
    {
        return $this->get('model_class');
    }

    public function getModelId()
    {
        return $this->get('model_id');
    }
}

/**
 * Database Query Event
 */
class DatabaseQuery extends Event
{
    public function __construct($sql, $bindings = [], $time = null)
    {
        parent::__construct([
            'sql' => $sql,
            'bindings' => $bindings,
            'execution_time' => $time,
            'action' => 'query'
        ]);
    }

    public function getSql()
    {
        return $this->get('sql');
    }

    public function getBindings()
    {
        return $this->get('bindings');
    }

    public function getExecutionTime()
    {
        return $this->get('execution_time');
    }

    public function getFormattedSql()
    {
        $sql = $this->getSql();
        $bindings = $this->getBindings();

        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'{$binding}'" : $binding;
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
}