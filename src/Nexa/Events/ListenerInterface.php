<?php

namespace Nexa\Events;

interface ListenerInterface
{
    /**
     * Handle the event
     *
     * @param Event $event
     * @return void
     */
    public function handle(Event $event);

    /**
     * Get the events this listener should listen to
     * Can return a string (single event) or array (multiple events)
     *
     * @return string|array
     */
    public function getEvents();

    /**
     * Get the priority of this listener (higher = executed first)
     * Default is 0
     *
     * @return int
     */
    public function getPriority();

    /**
     * Whether this listener should be executed only once
     *
     * @return bool
     */
    public function isOnce();
}