<?php

namespace Nexa\Events;

abstract class Listener implements ListenerInterface
{
    /**
     * Priority of this listener (higher = executed first)
     */
    protected $priority = 0;

    /**
     * Whether this listener should be executed only once
     */
    protected $once = false;

    /**
     * Events this listener should listen to
     */
    protected $events = [];

    /**
     * Handle the event - must be implemented by child classes
     *
     * @param Event $event
     * @return void
     */
    abstract public function handle(Event $event);

    /**
     * Get the events this listener should listen to
     *
     * @return string|array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Get the priority of this listener
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Whether this listener should be executed only once
     *
     * @return bool
     */
    public function isOnce()
    {
        return $this->once;
    }

    /**
     * Set the priority of this listener
     *
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set whether this listener should be executed only once
     *
     * @param bool $once
     * @return $this
     */
    public function setOnce($once = true)
    {
        $this->once = $once;
        return $this;
    }

    /**
     * Set the events this listener should listen to
     *
     * @param string|array $events
     * @return $this
     */
    public function setEvents($events)
    {
        $this->events = is_array($events) ? $events : [$events];
        return $this;
    }

    /**
     * Add an event to listen to
     *
     * @param string $event
     * @return $this
     */
    public function addEvent($event)
    {
        if (!in_array($event, $this->events)) {
            $this->events[] = $event;
        }
        return $this;
    }

    /**
     * Remove an event from listening
     *
     * @param string $event
     * @return $this
     */
    public function removeEvent($event)
    {
        $this->events = array_filter($this->events, function($e) use ($event) {
            return $e !== $event;
        });
        return $this;
    }
}