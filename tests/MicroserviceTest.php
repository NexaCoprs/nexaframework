<?php

namespace Tests;

use Nexa\Testing\TestCase;
use Nexa\Microservices\ServiceRegistry;
use Nexa\Microservices\ServiceClient;
use Exception;

class MicroserviceTest extends TestCase
{
    private $serviceRegistry;
    private $serviceClient;
    private $testServices;
    
    public function setUp()
    {
        parent::setUp();
        
        $this->serviceRegistry = new ServiceRegistry();
        $this->serviceClient = new ServiceClient($this->serviceRegistry);
        
        $this->testServices = [
            'user-service' => [
                'name' => 'user-service',
                'host' => '127.0.0.1',
                'port' => 8001,
                'health_check' => '/health',
                'version' => '1.0.0',
                'metadata' => ['type' => 'api']
            ],
            'order-service' => [
                'name' => 'order-service',
                'host' => '127.0.0.1',
                'port' => 8002,
                'health_check' => '/health',
                'version' => '1.0.0',
                'metadata' => ['type' => 'api']
            ],
            'notification-service' => [
                'name' => 'notification-service',
                'host' => '127.0.0.1',
                'port' => 8003,
                'health_check' => '/health',
                'version' => '1.0.0',
                'metadata' => ['type' => 'worker']
            ]
        ];
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        // Clean up registered services
        foreach ($this->testServices as $service) {
            $this->serviceRegistry->deregister($service['name']);
        }
    }
    
    public function testServiceRegistryInitialization()
    {
        $this->assertInstanceOf(ServiceRegistry::class, $this->serviceRegistry);
    }
    
    public function testServiceClientInitialization()
    {
        $this->assertInstanceOf(ServiceClient::class, $this->serviceClient);
    }
    
    public function testServiceRegistration()
    {
        $service = $this->testServices['user-service'];
        
        $result = $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        $this->assertTrue($result);
        $this->assertTrue($this->serviceRegistry->isRegistered($service['name']));
    }
    
    public function testServiceDeregistration()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        $result = $this->serviceRegistry->deregister($service['name']);
        
        $this->assertTrue($result);
        $this->assertFalse($this->serviceRegistry->isRegistered($service['name']));
    }
    
    public function testServiceDiscovery()
    {
        // Register multiple services
        foreach ($this->testServices as $service) {
            $this->serviceRegistry->register(
                $service['name'],
                $service['host'],
                $service['port'],
                $service
            );
        }
        
        $discoveredService = $this->serviceRegistry->discover('user-service');
        
        $this->assertNotNull($discoveredService);
        $this->assertEquals('user-service', $discoveredService['name']);
        $this->assertEquals('127.0.0.1', $discoveredService['host']);
        $this->assertEquals(8001, $discoveredService['port']);
    }
    
    public function testServiceDiscoveryByType()
    {
        // Register services
        foreach ($this->testServices as $service) {
            $this->serviceRegistry->register(
                $service['name'],
                $service['host'],
                $service['port'],
                $service
            );
        }
        
        $apiServices = $this->serviceRegistry->discoverByMetadata(['type' => 'api']);
        
        $this->assertIsArray($apiServices);
        $this->assertCount(2, $apiServices); // user-service and order-service
    }
    
    public function testServiceHealthCheck()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Mock health check
        $isHealthy = $this->serviceRegistry->mockHealthCheck($service['name']);
        
        $this->assertTrue($isHealthy);
    }
    
    public function testServiceClientRequest()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Mock HTTP request
        $response = $this->serviceClient->mockGet('user-service', '/users/1');
        
        $this->assertIsArray($response);
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals(200, $response['status']);
    }
    
    public function testServiceClientPostRequest()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $response = $this->serviceClient->mockPost('user-service', '/users', $userData);
        
        $this->assertIsArray($response);
        $this->assertEquals(201, $response['status']);
        $this->assertArrayHasKey('data', $response);
    }
    
    public function testCircuitBreaker()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Configure circuit breaker
        $this->serviceClient->configureCircuitBreaker($service['name'], [
            'failure_threshold' => 3,
            'timeout' => 60,
            'retry_timeout' => 30
        ]);
        
        // Simulate failures
        for ($i = 0; $i < 3; $i++) {
            $this->serviceClient->mockFailedRequest('user-service', '/users/1');
        }
        
        // Circuit should be open now
        $this->assertTrue($this->serviceClient->isCircuitOpen('user-service'));
        
        // Request should fail fast
        $response = $this->serviceClient->mockGet('user-service', '/users/1');
        $this->assertEquals(503, $response['status']); // Service Unavailable
    }
    
    public function testRetryLogic()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Configure retry policy
        $this->serviceClient->configureRetryPolicy($service['name'], [
            'max_attempts' => 3,
            'delay' => 100, // milliseconds
            'backoff_multiplier' => 2
        ]);
        
        // Mock request that fails twice then succeeds
        $response = $this->serviceClient->mockRetryableRequest('user-service', '/users/1');
        
        $this->assertEquals(200, $response['status']);
        $this->assertEquals(3, $response['attempts']); // Should have retried
    }
    
    public function testLoadBalancing()
    {
        // Register multiple instances of the same service
        $instances = [
            ['host' => '127.0.0.1', 'port' => 8001],
            ['host' => '127.0.0.1', 'port' => 8011],
            ['host' => '127.0.0.1', 'port' => 8021]
        ];
        
        foreach ($instances as $i => $instance) {
            $this->serviceRegistry->register(
                'user-service',
                $instance['host'],
                $instance['port'],
                array_merge($this->testServices['user-service'], $instance, ['instance_id' => $i])
            );
        }
        
        // Test round-robin load balancing
        $this->serviceClient->setLoadBalancingStrategy('round_robin');
        
        $usedPorts = [];
        for ($i = 0; $i < 6; $i++) {
            $instance = $this->serviceClient->selectInstance('user-service');
            $usedPorts[] = $instance['port'];
        }
        
        // Should cycle through all instances
        $this->assertContains(8001, $usedPorts);
        $this->assertContains(8011, $usedPorts);
        $this->assertContains(8021, $usedPorts);
    }
    
    public function testServiceMetrics()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Make some requests
        $this->serviceClient->mockGet('user-service', '/users/1');
        $this->serviceClient->mockPost('user-service', '/users', ['name' => 'Test']);
        $this->serviceClient->mockFailedRequest('user-service', '/users/999');
        
        $metrics = $this->serviceClient->getMetrics('user-service');
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_requests', $metrics);
        $this->assertArrayHasKey('successful_requests', $metrics);
        $this->assertArrayHasKey('failed_requests', $metrics);
        $this->assertArrayHasKey('average_response_time', $metrics);
        
        $this->assertEquals(3, $metrics['total_requests']);
        $this->assertEquals(2, $metrics['successful_requests']);
        $this->assertEquals(1, $metrics['failed_requests']);
    }
    
    public function testServiceTimeout()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Configure timeout
        $this->serviceClient->setTimeout(1000); // 1 second
        
        // Mock slow request
        $response = $this->serviceClient->mockSlowRequest('user-service', '/users/1', 2000);
        
        $this->assertEquals(408, $response['status']); // Request Timeout
    }
    
    public function testServiceAuthentication()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Set authentication token
        $this->serviceClient->setAuthToken('bearer-token-123');
        
        $response = $this->serviceClient->mockGet('user-service', '/protected/users/1');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('Authorization', $response['headers']);
    }
    
    public function testServiceVersioning()
    {
        // Register different versions of the same service
        $v1Service = array_merge($this->testServices['user-service'], [
            'version' => '1.0.0',
            'port' => 8001
        ]);
        
        $v2Service = array_merge($this->testServices['user-service'], [
            'version' => '2.0.0',
            'port' => 8002
        ]);
        
        $this->serviceRegistry->register('user-service', $v1Service['host'], $v1Service['port'], $v1Service);
        $this->serviceRegistry->register('user-service', $v2Service['host'], $v2Service['port'], $v2Service);
        
        // Request specific version
        $v1Instance = $this->serviceRegistry->discoverByVersion('user-service', '1.0.0');
        $v2Instance = $this->serviceRegistry->discoverByVersion('user-service', '2.0.0');
        
        $this->assertEquals(8001, $v1Instance['port']);
        $this->assertEquals(8002, $v2Instance['port']);
    }
    
    public function testDistributedTracing()
    {
        $service = $this->testServices['user-service'];
        
        $this->serviceRegistry->register(
            $service['name'],
            $service['host'],
            $service['port'],
            $service
        );
        
        // Enable tracing
        $this->serviceClient->enableTracing(true);
        
        $traceId = 'trace-' . uniqid();
        $this->serviceClient->setTraceId($traceId);
        
        $response = $this->serviceClient->mockGet('user-service', '/users/1');
        
        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('X-Trace-Id', $response['headers']);
        $this->assertEquals($traceId, $response['headers']['X-Trace-Id']);
    }
    
    public function testServiceDiscoveryFailure()
    {
        $this->expectException(Exception::class);
        
        // Try to discover non-existent service
        $this->serviceRegistry->discover('non-existent-service');
    }
    
    public function testServiceRegistryConsulIntegration()
    {
        $consulRegistry = new ServiceRegistry(['type' => 'consul', 'host' => 'localhost', 'port' => 8500]);
        
        // Mock Consul registration
        $result = $consulRegistry->mockConsulRegister('test-service', '127.0.0.1', 8080);
        
        $this->assertTrue($result);
    }
    
    public function testServiceRegistryEtcdIntegration()
    {
        $etcdRegistry = new ServiceRegistry(['type' => 'etcd', 'host' => 'localhost', 'port' => 2379]);
        
        // Mock etcd registration
        $result = $etcdRegistry->mockEtcdRegister('test-service', '127.0.0.1', 8080);
        
        $this->assertTrue($result);
    }
    
    public function testServiceRegistryRedisIntegration()
    {
        $redisRegistry = new ServiceRegistry(['type' => 'redis', 'host' => 'localhost', 'port' => 6379]);
        
        // Mock Redis registration
        $result = $redisRegistry->mockRedisRegister('test-service', '127.0.0.1', 8080);
        
        $this->assertTrue($result);
    }
}