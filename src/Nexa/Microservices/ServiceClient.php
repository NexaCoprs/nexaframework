<?php

namespace Nexa\Microservices;

class ServiceClient
{
    private $serviceRegistry;
    private $timeout = 30;
    private $retries = 3;
    private $circuitBreaker = [];
    private $retryPolicies = [];
    private $loadBalancingStrategy = 'round_robin';
    private $loadBalancingState = [];
    private $metrics = [];
    private $authTokens = [];
    private $tracingEnabled = false;
    private $traceId = null;

    public function __construct(ServiceRegistry $serviceRegistry)
    {
        $this->serviceRegistry = $serviceRegistry;
    }

    /**
     * Make a request to a service
     */
    public function request($serviceName, $endpoint, $method = 'GET', $data = null, $headers = [])
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        $url = "http://{$service['host']}:{$service['port']}{$endpoint}";
        
        // Mock implementation for testing
        return [
            'status' => 200,
            'data' => ['message' => 'Mock response from ' . $serviceName],
            'url' => $url,
            'method' => $method
        ];
    }

    /**
     * Make a GET request
     */
    public function get($serviceName, $endpoint, $headers = [])
    {
        return $this->request($serviceName, $endpoint, 'GET', null, $headers);
    }

    /**
     * Mock GET request for testing
     */
    public function mockGet($serviceName, $endpoint, $mockResponse = null, $headers = [])
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        $url = "http://{$service['host']}:{$service['port']}{$endpoint}";
        
        // Add tracing headers if enabled
        $responseHeaders = [];
        if ($this->tracingEnabled && $this->traceId) {
            $responseHeaders['X-Trace-Id'] = $this->traceId;
        }
        
        return $mockResponse ?: [
            'status' => 200,
            'data' => ['message' => 'Mock GET response from ' . $serviceName],
            'url' => $url,
            'method' => 'GET',
            'headers' => $responseHeaders,
            'mock' => true
        ];
    }

    /**
     * Make a POST request
     */
    public function post($serviceName, $endpoint, $data = null, $headers = [])
    {
        return $this->request($serviceName, $endpoint, 'POST', $data, $headers);
    }

    /**
     * Mock POST request for testing
     */
    public function mockPost($serviceName, $endpoint, $data = null, $mockResponse = null, $headers = [])
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        $url = "http://{$service['host']}:{$service['port']}{$endpoint}";
        
        return $mockResponse ?: [
            'status' => 201,
            'data' => ['message' => 'Mock POST response from ' . $serviceName, 'created' => true],
            'url' => $url,
            'method' => 'POST',
            'request_data' => $data,
            'mock' => true
        ];
    }

    /**
     * Make a PUT request
     */
    public function put($serviceName, $endpoint, $data = null, $headers = [])
    {
        return $this->request($serviceName, $endpoint, 'PUT', $data, $headers);
    }

    /**
     * Make a DELETE request
     */
    public function delete($serviceName, $endpoint, $headers = [])
    {
        return $this->request($serviceName, $endpoint, 'DELETE', null, $headers);
    }

    /**
     * Set request timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Set retry count
     */
    public function setRetries($retries)
    {
        $this->retries = $retries;
        return $this;
    }

    /**
     * Get service registry
     */
    public function getServiceRegistry()
    {
        return $this->serviceRegistry;
    }

    /**
     * Check if service is available
     */
    public function isServiceAvailable($serviceName)
    {
        return $this->serviceRegistry->has($serviceName);
    }

    /**
     * Discover services by type
     */
    public function discoverServices($type)
    {
        return $this->serviceRegistry->getByType($type);
    }

    /**
     * Configure circuit breaker for a service
     */
    public function configureCircuitBreaker($serviceName, $config = [])
    {
        $defaultConfig = [
            'failure_threshold' => 5,
            'timeout' => 60,
            'retry_timeout' => 30
        ];
        
        $this->circuitBreaker[$serviceName] = array_merge($defaultConfig, $config);
        return $this;
    }

    /**
     * Get circuit breaker status for a service
     */
    public function getCircuitBreakerStatus($serviceName)
    {
        return $this->circuitBreaker[$serviceName] ?? null;
    }

    /**
     * Reset circuit breaker for a service
     */
    public function resetCircuitBreaker($serviceName)
    {
        if (isset($this->circuitBreaker[$serviceName])) {
            $this->circuitBreaker[$serviceName]['failures'] = 0;
            $this->circuitBreaker[$serviceName]['last_failure'] = null;
            $this->circuitBreaker[$serviceName]['state'] = 'closed';
        }
        return $this;
    }

    /**
     * Mock a failed request for testing circuit breaker
     */
    public function mockFailedRequest($serviceName, $endpoint)
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        // Update circuit breaker state
        if (isset($this->circuitBreaker[$serviceName])) {
            $this->circuitBreaker[$serviceName]['failures'] = ($this->circuitBreaker[$serviceName]['failures'] ?? 0) + 1;
            $this->circuitBreaker[$serviceName]['last_failure'] = time();
            
            if ($this->circuitBreaker[$serviceName]['failures'] >= $this->circuitBreaker[$serviceName]['failure_threshold']) {
                $this->circuitBreaker[$serviceName]['state'] = 'open';
            }
        }

        return [
            'status' => 500,
            'error' => 'Service unavailable',
            'service' => $serviceName,
            'endpoint' => $endpoint,
            'mock' => true
        ];
    }

    /**
     * Check if circuit breaker is open for a service
     */
    public function isCircuitOpen($serviceName)
    {
        if (!isset($this->circuitBreaker[$serviceName])) {
            return false;
        }
        
        $breaker = $this->circuitBreaker[$serviceName];
        return isset($breaker['state']) && $breaker['state'] === 'open';
    }

    /**
     * Configure retry policy for a service
     */
    public function configureRetryPolicy($serviceName, $config = [])
    {
        $defaultConfig = [
            'max_attempts' => 3,
            'delay' => 1000, // milliseconds
            'backoff_multiplier' => 2,
            'max_delay' => 30000 // milliseconds
        ];
        
        $this->retryPolicies[$serviceName] = array_merge($defaultConfig, $config);
        return $this;
    }

    /**
     * Get retry policy for a service
     */
    public function getRetryPolicy($serviceName)
    {
        return $this->retryPolicies[$serviceName] ?? null;
    }

    /**
     * Mock a retryable request for testing
     */
    public function mockRetryableRequest($serviceName, $endpoint, $shouldSucceed = false)
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        $retryPolicy = $this->getRetryPolicy($serviceName);
        $maxAttempts = $retryPolicy['max_attempts'] ?? 3;
        
        if ($shouldSucceed) {
            return [
                'status' => 200,
                'data' => ['message' => 'Success after retry'],
                'attempts' => $maxAttempts,
                'service' => $serviceName,
                'endpoint' => $endpoint,
                'mock' => true
            ];
        } else {
            return [
                'status' => 500,
                'error' => 'Failed after all retry attempts',
                'attempts' => $maxAttempts,
                'service' => $serviceName,
                'endpoint' => $endpoint,
                'mock' => true
            ];
        }
    }

    /**
     * Set load balancing strategy
     */
    public function setLoadBalancingStrategy($strategy)
    {
        $this->loadBalancingStrategy = $strategy;
        return $this;
    }

    /**
     * Get load balancing strategy
     */
    public function getLoadBalancingStrategy()
    {
        return $this->loadBalancingStrategy;
    }

    /**
     * Select service instance based on load balancing strategy
     */
    public function selectServiceInstance($serviceName)
    {
        $services = $this->serviceRegistry->getByType('api');
        $matchingServices = array_filter($services, function($service) use ($serviceName) {
            return isset($service['name']) && $service['name'] === $serviceName;
        });
        
        if (empty($matchingServices)) {
            return null;
        }
        
        switch ($this->loadBalancingStrategy) {
            case 'round_robin':
                if (!isset($this->loadBalancingState[$serviceName])) {
                    $this->loadBalancingState[$serviceName] = 0;
                }
                $instances = array_values($matchingServices);
                $selected = $instances[$this->loadBalancingState[$serviceName] % count($instances)];
                $this->loadBalancingState[$serviceName]++;
                return $selected;
                
            case 'random':
                $instances = array_values($matchingServices);
                return $instances[array_rand($instances)];
                
            default:
                return array_values($matchingServices)[0];
        }
    }

    /**
     * Alias for selectServiceInstance
     */
    public function selectInstance($serviceName)
    {
        return $this->selectServiceInstance($serviceName);
    }

    /**
     * Get service metrics
     */
    public function getMetrics($serviceName = null)
    {
        if ($serviceName) {
            return $this->metrics[$serviceName] ?? [
                'requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time' => 0,
                'circuit_breaker_trips' => 0,
                'retry_attempts' => 0
            ];
        }
        
        return $this->metrics;
    }

    /**
     * Record metrics for a service request
     */
    public function recordMetrics($serviceName, $responseTime, $success = true)
    {
        if (!isset($this->metrics[$serviceName])) {
            $this->metrics[$serviceName] = [
                'requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_response_time' => 0,
                'average_response_time' => 0,
                'circuit_breaker_trips' => 0,
                'retry_attempts' => 0
            ];
        }
        
        $this->metrics[$serviceName]['requests']++;
        $this->metrics[$serviceName]['total_response_time'] += $responseTime;
        $this->metrics[$serviceName]['average_response_time'] = 
            $this->metrics[$serviceName]['total_response_time'] / $this->metrics[$serviceName]['requests'];
        
        if ($success) {
            $this->metrics[$serviceName]['successful_requests']++;
        } else {
            $this->metrics[$serviceName]['failed_requests']++;
        }
    }

    /**
     * Reset metrics for a service or all services
     */
    public function resetMetrics($serviceName = null)
    {
        if ($serviceName) {
            unset($this->metrics[$serviceName]);
        } else {
            $this->metrics = [];
        }
    }

    /**
     * Mock a slow request for timeout testing
     */
    public function mockSlowRequest($serviceName, $endpoint, $delay = 5)
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        // Simulate timeout if delay exceeds configured timeout
        if ($delay > $this->timeout) {
            throw new \Exception("Request timeout after {$this->timeout} seconds");
        }
        
        return [
            'status' => 200,
            'data' => ['message' => 'Slow response'],
            'delay' => $delay,
            'service' => $serviceName,
            'endpoint' => $endpoint,
            'mock' => true
        ];
    }

    /**
     * Set authentication token for a service
     */
    public function setAuthToken($serviceNameOrToken, $token = null)
    {
        if ($token === null) {
            // Single parameter - set default token
            $this->authTokens['default'] = $serviceNameOrToken;
        } else {
            // Two parameters - set token for specific service
            $this->authTokens[$serviceNameOrToken] = $token;
        }
        return $this;
    }

    /**
     * Get authentication token for a service
     */
    public function getAuthToken($serviceName = null)
    {
        if ($serviceName === null) {
            return $this->authTokens['default'] ?? null;
        }
        return $this->authTokens[$serviceName] ?? $this->authTokens['default'] ?? null;
    }

    /**
     * Enable or disable distributed tracing
     */
    public function enableTracing($enabled)
    {
        $this->tracingEnabled = $enabled;
    }

    /**
     * Set trace ID for distributed tracing
     */
    public function setTraceId($traceId)
    {
        $this->traceId = $traceId;
    }

    /**
     * Remove authentication token for a service
     */
    public function removeAuthToken($serviceName)
    {
        unset($this->authTokens[$serviceName]);
        return $this;
    }

    /**
     * Mock an authenticated request for testing
     */
    public function mockAuthenticatedRequest($serviceName, $endpoint, $requiresAuth = true)
    {
        $service = $this->serviceRegistry->get($serviceName);
        if (!$service) {
            throw new \Exception("Service '{$serviceName}' not found in registry");
        }

        $token = $this->getAuthToken($serviceName);
        
        if ($requiresAuth && !$token) {
            return [
                'status' => 401,
                'error' => 'Authentication required',
                'service' => $serviceName,
                'endpoint' => $endpoint,
                'mock' => true
            ];
        }
        
        return [
            'status' => 200,
            'data' => ['message' => 'Authenticated response', 'user' => 'test_user'],
            'authenticated' => true,
            'token' => $token,
            'service' => $serviceName,
            'endpoint' => $endpoint,
            'mock' => true
        ];
    }
}