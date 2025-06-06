<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\Queue\QueueManager;
use Nexa\Queue\Job;
use Nexa\Queue\SyncQueueDriver;
use Nexa\Queue\DatabaseQueueDriver;
use Nexa\Queue\Jobs\SendEmailJob;
use Nexa\Queue\Jobs\ProcessImageJob;

class QueueTest extends TestCase
{
    private $queueManager;
    
    public function setUp()
    {
        parent::setUp();
        $this->queueManager = new QueueManager();
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        // Clean up any test jobs
        if ($this->queueManager->getDriver() instanceof DatabaseQueueDriver) {
            $this->queueManager->clear('test');
        }
    }
    
    public function testSyncQueueDriverExecution()
    {
        $driver = new SyncQueueDriver();
        $job = new TestJob(['message' => 'Hello World']);
        
        // Push job (should execute immediately in sync driver)
        $result = $driver->push($job, 'test');
        
        $this->assertTrue($result);
        $this->assertTrue($job->wasExecuted());
    }
    
    public function testDatabaseQueueDriverPushAndPop()
    {
        $driver = new DatabaseQueueDriver($this->db);
        $job = new TestJob(['message' => 'Test Message']);
        
        // Push job
        $jobId = $driver->push($job, 'test');
        $this->assertNotNull($jobId);
        
        // Check queue size
        $size = $driver->size('test');
        $this->assertEquals(1, $size);
        
        // Pop job
        $poppedJob = $driver->pop('test');
        $this->assertNotNull($poppedJob);
        $this->assertEquals('TestJob', $poppedJob->getName());
        
        // Queue should be empty now
        $size = $driver->size('test');
        $this->assertEquals(0, $size);
    }
    
    public function testQueueManagerJobProcessing()
    {
        $job = new TestJob(['message' => 'Process Me']);
        
        // Push job to queue
        $this->queueManager->push($job, 'test');
        
        // Process one job
        $processed = $this->queueManager->processJob('test');
        
        $this->assertTrue($processed);
    }
    
    public function testJobFailureHandling()
    {
        $job = new FailingTestJob(['should_fail' => true]);
        
        // Push failing job
        $this->queueManager->push($job, 'test');
        
        // Process job (should fail)
        $processed = $this->queueManager->processJob('test');
        
        // Job should be processed but failed
        $this->assertTrue($processed);
    }
    
    public function testJobRetryMechanism()
    {
        $job = new RetryableTestJob(['attempt_count' => 0]);
        
        // Set max attempts to 3
        $job->setMaxAttempts(3);
        
        $this->queueManager->push($job, 'test');
        
        // Process job multiple times (should retry)
        for ($i = 0; $i < 4; $i++) {
            $this->queueManager->processJob('test');
        }
        
        // Job should have been attempted 3 times
        $this->assertEquals(3, $job->getAttempts());
    }
    
    public function testDelayedJobExecution()
    {
        $job = new TestJob(['message' => 'Delayed Job']);
        $delay = 2; // 2 seconds
        
        // Push job with delay
        $this->queueManager->pushWithDelay($job, 'test', $delay);
        
        // Job should not be available immediately
        $driver = $this->queueManager->getDriver();
        if ($driver instanceof DatabaseQueueDriver) {
            $poppedJob = $driver->pop('test');
            $this->assertNull($poppedJob); // Should be null due to delay
        }
    }
    
    public function testSendEmailJobExecution()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Email',
            'message' => 'This is a test email',
            'from' => 'sender@example.com'
        ];
        
        $job = new SendEmailJob($emailData);
        
        // Test job creation
        $this->assertEquals('emails', $job->getQueue());
        $this->assertEquals(3, $job->getMaxAttempts());
        $this->assertEquals(30, $job->getTimeout());
        
        // Test data access
        $this->assertEquals('test@example.com', $job->get('to'));
        $this->assertEquals('Test Email', $job->get('subject'));
    }
    
    public function testProcessImageJobCreation()
    {
        $imageData = [
            'image_path' => '/path/to/image.jpg',
            'sizes' => [
                'thumbnail' => [150, 150],
                'medium' => [300, 300]
            ],
            'output_dir' => '/path/to/output/'
        ];
        
        $job = new ProcessImageJob($imageData);
        
        // Test job properties
        $this->assertEquals('images', $job->getQueue());
        $this->assertEquals(2, $job->getMaxAttempts());
        $this->assertEquals(120, $job->getTimeout());
        
        // Test data access
        $this->assertEquals('/path/to/image.jpg', $job->get('image_path'));
        $this->assertEquals('/path/to/output/', $job->get('output_dir'));
    }
    
    public function testJobSerialization()
    {
        $job = new TestJob(['message' => 'Serialize Me']);
        
        // Test to array
        $array = $job->toArray();
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('attempts', $array);
        
        // Test to JSON
        $json = $job->toJson();
        $this->assertTrue(is_string($json));
        $decoded = json_decode($json, true);
        $this->assertNotNull($decoded);
        
        // Test from array
        $newJob = TestJob::fromArray($array);
        $this->assertEquals($job->getId(), $newJob->getId());
        $this->assertEquals($job->getName(), $newJob->getName());
    }
    
    public function testQueueClearOperation()
    {
        // Add multiple jobs
        for ($i = 0; $i < 5; $i++) {
            $job = new TestJob(['message' => "Job $i"]);
            $this->queueManager->push($job, 'test');
        }
        
        // Verify jobs were added
        $driver = $this->queueManager->getDriver();
        if ($driver instanceof DatabaseQueueDriver) {
            $size = $driver->size('test');
            $this->assertEquals(5, $size);
        }
        
        // Clear queue
        $this->queueManager->clear('test');
        
        // Verify queue is empty
        if ($driver instanceof DatabaseQueueDriver) {
            $size = $driver->size('test');
            $this->assertEquals(0, $size);
        }
    }
    
    public function testJobTimeout()
    {
        $job = new TestJob(['message' => 'Timeout Test']);
        $job->setTimeout(1); // 1 second timeout
        
        $this->assertEquals(1, $job->getTimeout());
    }
    
    public function testJobDelay()
    {
        $job = new TestJob(['message' => 'Delay Test']);
        $job->setDelay(5); // 5 second delay
        
        $this->assertEquals(5, $job->getDelay());
    }
    
    public function testJobQueueAssignment()
    {
        $job = new TestJob(['message' => 'Queue Test']);
        $job->setQueue('custom-queue');
        
        $this->assertEquals('custom-queue', $job->getQueue());
    }
}

// Test job classes
class TestJob extends Job
{
    private $executed = false;
    
    public function handle()
    {
        $this->executed = true;
        // Simulate some work
        usleep(10000); // 0.01 seconds
    }
    
    public function wasExecuted()
    {
        return $this->executed;
    }
    
    public function shouldRetry(\Exception $exception)
    {
        return false;
    }
    
    public function retry($jobId)
    {
        // Implementation for retry method
        return false;
    }
}

class FailingTestJob extends Job
{
    public function handle()
    {
        if ($this->get('should_fail', false)) {
            throw new \Exception('Job intentionally failed');
        }
    }
    
    public function shouldRetry(\Exception $exception)
    {
        return false; // Don't retry failing jobs
    }
    
    public function retry($jobId)
    {
        // Implementation for retry method
        return false;
    }
}

class RetryableTestJob extends Job
{
    public function handle()
    {
        $count = $this->get('attempt_count', 0);
        $this->set('attempt_count', $count + 1);
        
        // Always fail to test retry mechanism
        throw new \Exception('Retryable job failed');
    }
    
    public function shouldRetry(\Exception $exception)
    {
        return $this->getAttempts() < $this->getMaxAttempts();
    }
    
    public function retry($jobId)
    {
        // Implementation for retry method
        return true;
    }
}