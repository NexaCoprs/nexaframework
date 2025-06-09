<?php

namespace Nexa\Events;

abstract class Event
{
    /**
     * Event data
     */
    protected $data;

    /**
     * Event timestamp
     */
    protected $timestamp;

    /**
     * Event name
     */
    protected $name;

    /**
     * Whether the event should be stopped from propagating
     */
    protected $stopped = false;

    public function __construct($data = [])
    {
        $this->data = $data;
        $this->timestamp = microtime(true);
        $this->name = static::class;
    }

    /**
     * Get event data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set event data
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get specific data by key
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set specific data by key
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get event timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get event name
     */
    public function getName()
    {
        // Return just the class name without namespace
        $className = $this->name ?: static::class;
        return basename(str_replace('\\', '/', $className));
    }

    /**
     * Stop event propagation
     */
    public function stopPropagation()
    {
        $this->stopped = true;
        return $this;
    }

    /**
     * Check if event propagation is stopped
     */
    public function isPropagationStopped()
    {
        return $this->stopped;
    }

    /**
     * Convert event to array
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'data' => $this->data,
            'timestamp' => $this->timestamp,
            'stopped' => $this->stopped
        ];
    }

    /**
     * Convert event to JSON
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}