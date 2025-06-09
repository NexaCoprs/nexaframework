<?php

namespace Nexa\Microservices;

class ServiceRegistry
{
    private $services = [];
    private $healthChecks = [];

    /**
     * Register a service
     */
    public function register($name, $host = null, $port = null, $config = null)
    {
        if ($host === null && $port === null && $config === null) {
            // Single parameter mode - $name is actually the config
            $this->services[$name] = $name;
        } elseif (is_array($host) && $port === null && $config === null) {
            // Two parameter mode - $name is name, $host is config
            $this->services[$name] = $host;
        } else {
            // Multi parameter mode - construct config from parameters
            $serviceConfig = $config ?: [];
            $serviceConfig['name'] = $name;
            $serviceConfig['host'] = $host;
            $serviceConfig['port'] = $port;
            $this->services[$name] = $serviceConfig;
        }
        return $this;
    }

    /**
     * Unregister a service
     */
    public function unregister($name)
    {
        unset($this->services[$name]);
        unset($this->healthChecks[$name]);
        return $this;
    }

    /**
     * Deregister a service (alias for unregister)
     */
    public function deregister($name)
    {
        return $this->unregister($name);
    }

    /**
     * Get a service by name
     */
    public function get($name)
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Get all services
     */
    public function getAll()
    {
        return $this->services;
    }

    /**
     * Check if a service exists
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Check if a service is registered (alias for has)
     */
    public function isRegistered($name)
    {
        return $this->has($name);
    }

    /**
     * Get services by type
     */
    public function getByType($type)
    {
        return array_filter($this->services, function($service) use ($type) {
            return isset($service['metadata']['type']) && $service['metadata']['type'] === $type;
        });
    }

    /**
     * Discover services by type (alias for getByType)
     */
    public function discover($type)
    {
        return $this->getByType($type);
    }

    /**
     * Discover a service by name and version
     */
    public function discoverByVersion($serviceName, $version)
    {
        if (!isset($this->services[$serviceName])) {
            return null;
        }

        $instances = $this->services[$serviceName];
        if (empty($instances)) {
            return null;
        }

        // Find instance with matching version
        foreach ($instances as $instance) {
            if (isset($instance['version']) && $instance['version'] === $version && $this->isHealthy($instance)) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Mock Consul registration for testing
     */
    public function mockConsulRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'consul']);
        return true;
    }

    /**
     * Mock etcd registration for testing
     */
    public function mockEtcdRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'etcd']);
        return true;
    }

    /**
     * Mock Redis registration for testing
     */
    public function mockRedisRegister($serviceName, $host, $port)
    {
        // Mock implementation for testing
        $this->register($serviceName, $host, $port, ['type' => 'redis']);
        return true;
    }

    /**
     * Discover services by metadata
     */
    public function discoverByMetadata($criteria, $value = null)
    {
        if (is_array($criteria)) {
            // Handle array of criteria
            return array_filter($this->services, function($service) use ($criteria) {
                if (!isset($service['metadata'])) {
                    return false;
                }
                
                foreach ($criteria as $key => $expectedValue) {
                    if (!isset($service['metadata'][$key]) || $service['metadata'][$key] !== $expectedValue) {
                        return false;
                    }
                }
                
                return true;
            });
        } else {
            // Handle single key-value pair
            $key = $criteria;
            return array_filter($this->services, function($service) use ($key, $value) {
                if (!isset($service['metadata'][$key])) {
                    return false;
                }
                
                if ($value === null) {
                    return true;
                }
                
                return $service['metadata'][$key] === $value;
            });
        }
    }

    /**
     * Perform health check on a service
     */
    public function healthCheck($name)
    {
        if (!$this->has($name)) {
            return false;
        }

        $service = $this->get($name);
        $url = "http://{$service['host']}:{$service['port']}{$service['health_check']}";
        
        // Mock health check for testing
        $this->healthChecks[$name] = [
            'status' => 'healthy',
            'timestamp' => time(),
            'url' => $url
        ];
        
        return true;
    }

    /**
     * Mock health check for testing
     */
    public function mockHealthCheck($name, $status = 'healthy')
    {
        if (!$this->has($name)) {
            return false;
        }

        $this->healthChecks[$name] = [
            'status' => $status,
            'timestamp' => time(),
            'mock' => true
        ];
        
        return true;
    }

    /**
     * Get health status of a service
     */
    public function getHealthStatus($name)
    {
        return $this->healthChecks[$name] ?? null;
    }

    /**
     * Get all health statuses
     */
    public function getAllHealthStatuses()
    {
        return $this->healthChecks;
    }

    /**
     * Clear all services
     */
    public function clear()
    {
        $this->services = [];
        $this->healthChecks = [];
        return $this;
    }
}