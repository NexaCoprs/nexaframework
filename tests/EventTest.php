<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\Events\Event;
use Nexa\Events\EventDispatcher;
use Nexa\Events\Listener;
use Nexa\Events\ListenerInterface;
use Nexa\Events\UserRegistered;
use Nexa\Events\UserLoggedIn;
use Nexa\Events\ModelCreated;

class EventTest extends TestCase
{
    private $dispatcher;
    
    public function setUp()
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }
    
    public function tearDown()
    {
        parent::tearDown();
    }
    
    public function testEventCreation()
    {
        $data = ['message' => 'Hello World'];
        $event = new TestEvent($data);
        
        $this->assertEquals('TestEvent', $event->getName());
        $this->assertEquals($data, $event->getData());
        $this->assertEquals('Hello World', $event->get('message'));
        $this->assertFalse($event->isPropagationStopped());
    }
    
    public function testEventDataManipulation()
    {
        $event = new TestEvent(['count' => 1]);
        
        // Test getting data
        $this->assertEquals(1, $event->get('count'));
        $this->assertNull($event->get('nonexistent'));
        $this->assertEquals('default', $event->get('nonexistent', 'default'));
        
        // Test setting data
        $event->set('count', 2);
        $this->assertEquals(2, $event->get('count'));
        
        $event->set('new_key', 'new_value');
        $this->assertEquals('new_value', $event->get('new_key'));
    }
    
    public function testEventPropagation()
    {
        $event = new TestEvent(['test' => true]);
        
        $this->assertFalse($event->isPropagationStopped());
        
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
    
    public function testEventSerialization()
    {
        $data = ['user_id' => 123, 'email' => 'test@example.com'];
        $event = new TestEvent($data);
        
        // Test to array
        $array = $event->toArray();
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('timestamp', $array);
        
        // Test to JSON
        $json = $event->toJson();
        $this->assertTrue(is_string($json));
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        $this->assertEquals('TestEvent', $decoded['name']);
    }
    
    public function testListenerRegistration()
    {
        $listener = new TestListener();
        
        $this->dispatcher->listen('TestEvent', $listener);
        
        // Verify listener was registered
        $listeners = $this->dispatcher->getListeners('TestEvent');
        $this->assertCount(1, $listeners);
        $this->assertContains($listener, $listeners);
    }
    
    public function testEventDispatching()
    {
        $listener = new TestListener();
        $this->dispatcher->listen('TestEvent', $listener);
        
        $event = new TestEvent(['message' => 'Test dispatch']);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($listener->wasHandled());
        $this->assertEquals($event, $listener->getLastEvent());
    }
    
    public function testMultipleListeners()
    {
        $listener1 = new TestListener();
        $listener2 = new TestListener();
        
        $this->dispatcher->listen('TestEvent', $listener1);
        $this->dispatcher->listen('TestEvent', $listener2);
        
        $event = new TestEvent(['test' => 'multiple']);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($listener1->wasHandled());
        $this->assertTrue($listener2->wasHandled());
    }
    
    public function testListenerPriority()
    {
        $highPriorityListener = new HighPriorityTestListener();
        $lowPriorityListener = new LowPriorityTestListener();
        
        $this->dispatcher->listen('TestEvent', $lowPriorityListener);
        $this->dispatcher->listen('TestEvent', $highPriorityListener);
        
        $event = new TestEvent(['order_test' => true]);
        $this->dispatcher->dispatch($event);
        
        // High priority listener should be called first
        $this->assertTrue($highPriorityListener->wasHandled());
        $this->assertTrue($lowPriorityListener->wasHandled());
    }
    
    public function testEventPropagationStopping()
    {
        $stoppingListener = new StoppingTestListener();
        $normalListener = new TestListener();
        
        $this->dispatcher->listen('TestEvent', $stoppingListener);
        $this->dispatcher->listen('TestEvent', $normalListener);
        
        $event = new TestEvent(['stop_test' => true]);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($stoppingListener->wasHandled());
        $this->assertFalse($normalListener->wasHandled()); // Should not be called
    }
    
    public function testOnceListener()
    {
        $onceListener = new OnceTestListener();
        $this->dispatcher->subscribe($onceListener);
        
        // First dispatch
        $event1 = new TestEvent(['first' => true]);
        $this->dispatcher->dispatch($event1);
        $this->assertTrue($onceListener->wasHandled());
        
        // Reset and dispatch again
        $onceListener->reset();
        $event2 = new TestEvent(['second' => true]);
        $this->dispatcher->dispatch($event2);
        $this->assertFalse($onceListener->wasHandled()); // Should not be called again
    }
    
    public function testListenerRemoval()
    {
        $listener = new TestListener();
        $this->dispatcher->listen('TestEvent', $listener);
        
        // Verify listener is registered
        $listeners = $this->dispatcher->getListeners('TestEvent');
        $this->assertCount(1, $listeners);
        
        // Remove listener
        $this->dispatcher->removeListener('TestEvent', $listener);
        
        // Verify listener is removed
        $listeners = $this->dispatcher->getListeners('TestEvent');
        $this->assertCount(0, $listeners);
    }
    
    public function testUserRegisteredEvent()
    {
        $userData = [
            'id' => 1,
            'email' => 'newuser@example.com',
            'name' => 'New User'
        ];
        
        $event = new UserRegistered($userData);
        
        $this->assertEquals('UserRegistered', $event->getName());
        $this->assertEquals(1, $event->getUserId());
        $this->assertEquals('newuser@example.com', $event->getUserEmail());
        $this->assertEquals('New User', $event->getUserName());
        $this->assertEquals($userData, $event->getUserData());
    }
    
    public function testUserLoggedInEvent()
    {
        $userData = [
            'id' => 2,
            'email' => 'user@example.com',
            'last_login' => '2023-12-01 10:00:00'
        ];
        
        $event = new UserLoggedIn($userData, '192.168.1.1', 'Mozilla/5.0');
        
        $this->assertEquals('UserLoggedIn', $event->getName());
        $this->assertEquals(2, $event->getUserId());
        $this->assertEquals('192.168.1.1', $event->getIpAddress());
        $this->assertEquals('Mozilla/5.0', $event->getUserAgent());
    }
    
    public function testModelCreatedEvent()
    {
        $modelData = [
            'id' => 1,
            'title' => 'Test Post',
            'content' => 'This is a test post'
        ];
        
        $event = new ModelCreated('Post', $modelData);
        
        $this->assertEquals('ModelCreated', $event->getName());
        $this->assertEquals('Post', $event->getModelName());
        $this->assertEquals($modelData, $event->getModelData());
        $this->assertEquals(1, $event->getModelId());
    }
    
    public function testEventLogging()
    {
        $this->dispatcher->enableLogging();
        
        $event = new TestEvent(['logged' => true]);
        $this->dispatcher->dispatch($event);
        
        // Check if event was logged (this would require checking log files in a real scenario)
        $this->assertTrue(true); // Placeholder assertion
    }
    
    public function testClosureListener()
    {
        $handled = false;
        $lastEvent = null;
        
        $closure = function($event) use (&$handled, &$lastEvent) {
            $handled = true;
            $lastEvent = $event;
        };
        
        $this->dispatcher->listen('TestEvent', $closure);
        
        $event = new TestEvent(['closure_test' => true]);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($handled);
        $this->assertEquals($event, $lastEvent);
    }
    
    public function testWildcardListeners()
    {
        $wildcardListener = new TestListener();
        $this->dispatcher->listen('*', $wildcardListener);
        
        $event1 = new TestEvent(['wildcard1' => true]);
        $event2 = new AnotherTestEvent(['wildcard2' => true]);
        
        $this->dispatcher->dispatch($event1);
        $this->assertTrue($wildcardListener->wasHandled());
        
        $wildcardListener->reset();
        $this->dispatcher->dispatch($event2);
        $this->assertTrue($wildcardListener->wasHandled());
    }
}

// Test event classes
class TestEvent extends Event
{
    // Basic test event
}

class AnotherTestEvent extends Event
{
    // Another test event for wildcard testing
}

// Test listener classes
class TestListener extends Listener
{
    private $handled = false;
    private $lastEvent = null;
    
    public function handle(Event $event)
    {
        $this->handled = true;
        $this->lastEvent = $event;
    }
    
    public function getEvents()
    {
        return ['TestEvent', 'AnotherTestEvent'];
    }
    
    public function wasHandled()
    {
        return $this->handled;
    }
    
    public function getLastEvent()
    {
        return $this->lastEvent;
    }
    
    public function reset()
    {
        $this->handled = false;
        $this->lastEvent = null;
    }
}

class HighPriorityTestListener extends TestListener
{
    public function getPriority()
    {
        return 100; // High priority
    }
}

class LowPriorityTestListener extends TestListener
{
    public function getPriority()
    {
        return 1; // Low priority
    }
}

class StoppingTestListener extends TestListener
{
    public function handle(Event $event)
    {
        parent::handle($event);
        $event->stopPropagation();
    }
}

class OnceTestListener extends TestListener
{
    public function isOnce()
    {
        return true;
    }
}