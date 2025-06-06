<?php

namespace Nexa\Events;

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