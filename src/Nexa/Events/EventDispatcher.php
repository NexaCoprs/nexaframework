<?php

namespace Nexa\Events;

use Nexa\Logging\Logger;

class EventDispatcher
{
    /**
     * Registered listeners
     */
    private $listeners = [];

    /**
     * One-time listeners that have been executed
     */
    private $executedOnceListeners = [];

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Whether to log events
     */
    private $logEvents = false;

    public function __construct( $logger = null)
    {
        $this->logger = $logger;
        $this->logEvents = $_ENV['LOG_EVENTS'] ?? false;
    }

    /**
     * Register a listener for an event
     *
     * @param string $eventName
     * @param ListenerInterface|callable $listener
     * @param int $priority
     * @return $this
     */
    public function listen($eventName, $listener, $priority = 0)
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
            'once' => false
        ];

        // Sort by priority (higher first)
        usort($this->listeners[$eventName], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $this;
    }

    /**
     * Register a one-time listener for an event
     *
     * @param string $eventName
     * @param ListenerInterface|callable $listener
     * @param int $priority
     * @return $this
     */
    public function once($eventName, $listener, $priority = 0)
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
            'once' => true
        ];

        // Sort by priority (higher first)
        usort($this->listeners[$eventName], function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $this;
    }

    /**
     * Register a listener instance
     *
     * @param ListenerInterface $listener
     * @return $this
     */
    public function subscribe(ListenerInterface $listener)
    {
        $events = $listener->getEvents();
        $events = is_array($events) ? $events : [$events];

        foreach ($events as $eventName) {
            if ($listener->isOnce()) {
                $this->once($eventName, $listener, $listener->getPriority());
            } else {
                $this->listen($eventName, $listener, $listener->getPriority());
            }
        }

        return $this;
    }

    /**
     * Dispatch an event
     *
     * @param string|Event $event
     * @param array $data
     * @return Event
     */
    public function dispatch($event, $data = [])
    {
        // Create event instance if string is passed
        if (is_string($event)) {
            $eventName = $event;
            $event = new class($data, $eventName) extends Event {
                private $eventName;
                public function __construct($data, $name = null) {
                    parent::__construct($data);
                    $this->eventName = $name;
                }
                public function getName() {
                    return $this->eventName;
                }
            };
        } else {
            $eventName = $event->getName();
        }

        // Log event if enabled
        if ($this->logEvents && $this->logger) {
            $this->logger->info("Event dispatched: {$eventName}", [
                'event_data' => $event->getData(),
                'timestamp' => $event->getTimestamp()
            ]);
        }

        // Get listeners for this event
        $listeners = $this->listeners[$eventName] ?? [];

        // Execute listeners
        foreach ($listeners as $index => $listenerData) {
            $listener = $listenerData['listener'];
            $once = $listenerData['once'];

            // Skip if this is a one-time listener that has already been executed
            $listenerId = $this->getListenerId($listener);
            if ($once && in_array($listenerId, $this->executedOnceListeners)) {
                continue;
            }

            try {
                // Execute listener
                if ($listener instanceof ListenerInterface) {
                    $listener->handle($event);
                } elseif (is_callable($listener)) {
                    call_user_func($listener, $event);
                }

                // Mark one-time listener as executed
                if ($once) {
                    $this->executedOnceListeners[] = $listenerId;
                    // Remove from listeners array
                    unset($this->listeners[$eventName][$index]);
                }

                // Stop propagation if requested
                if ($event->isPropagationStopped()) {
                    break;
                }

            } catch (\Exception $e) {
                // Log listener error
                if ($this->logger) {
                    $this->logger->error("Error in event listener for {$eventName}", [
                        'error' => $e->getMessage(),
                        'listener' => get_class($listener),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                // Re-throw exception in development
                if ($_ENV['APP_ENV'] === 'development') {
                    throw $e;
                }
            }
        }

        return $event;
    }

    /**
     * Remove listeners for an event
     *
     * @param string $eventName
     * @param ListenerInterface|callable|null $listener
     * @return $this
     */
    public function forget($eventName, $listener = null)
    {
        if ($listener === null) {
            // Remove all listeners for the event
            unset($this->listeners[$eventName]);
        } else {
            // Remove specific listener
            if (isset($this->listeners[$eventName])) {
                $this->listeners[$eventName] = array_filter(
                    $this->listeners[$eventName],
                    function($listenerData) use ($listener) {
                        return $listenerData['listener'] !== $listener;
                    }
                );
            }
        }

        return $this;
    }

    /**
     * Get all listeners for an event
     *
     * @param string $eventName
     * @return array
     */
    public function getListeners($eventName)
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * Remove a listener for an event
     *
     * @param string $eventName
     * @param mixed $listener
     * @return $this
     */
    public function removeListener($eventName, $listener)
    {
        if (!isset($this->listeners[$eventName])) {
            return $this;
        }

        $listenerId = $this->getListenerId($listener);
        
        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            function($item) use ($listenerId) {
                return $this->getListenerId($item['listener']) !== $listenerId;
            }
        );

        // Remove the event entirely if no listeners remain
        if (empty($this->listeners[$eventName])) {
            unset($this->listeners[$eventName]);
        }

        return $this;
    }

    /**
     * Check if an event has listeners
     *
     * @param string $eventName
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Get all registered events
     *
     * @return array
     */
    public function getEvents()
    {
        return array_keys($this->listeners);
    }

    /**
     * Clear all listeners
     *
     * @return $this
     */
    public function clear()
    {
        $this->listeners = [];
        $this->executedOnceListeners = [];
        return $this;
    }

    /**
     * Get a unique identifier for a listener
     *
     * @param mixed $listener
     * @return string
     */
    private function getListenerId($listener)
    {
        if (is_object($listener)) {
            return spl_object_hash($listener);
        } elseif (is_array($listener)) {
            return serialize($listener);
        } else {
            return (string) $listener;
        }
    }

    /**
     * Enable event logging
     *
     * @return $this
     */
    public function enableLogging()
    {
        $this->logEvents = true;
        return $this;
    }

    /**
     * Disable event logging
     *
     * @return $this
     */
    public function disableLogging()
    {
        $this->logEvents = false;
        return $this;
    }

    /**
     * Enable or disable event logging
     *
     * @param bool $enabled
     * @return $this
     */
    public function setEventLogging($enabled)
    {
        $this->logEvents = $enabled;
        return $this;
    }

    /**
     * Set logger instance
     *
     * @param Logger $logger
     * @return $this
     */
    public function setLogger( $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}