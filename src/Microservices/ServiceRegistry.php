<?php

namespace Nexa\Microservices;

/**
 * Registre de services pour les microservices
 * 
 * Cette classe gère l'enregistrement et la découverte des services
 * dans une architecture de microservices.
 * 
 * @package Nexa\Microservices
 */
class ServiceRegistry
{
    /**
     * Services enregistrés
     *
     * @var array
     */
    protected $services = [];

    /**
     * Configuration du registre
     *
     * @var array
     */
    protected $config;

    /**
     * Client de découverte de services (Consul, etcd, etc.)
     *
     * @var mixed
     */
    protected $discoveryClient;

    /**
     * Cache des services
     *
     * @var array
     */
    protected $serviceCache = [];

    /**
     * Constructeur
     *
     * @param array $config Configuration du registre
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'discovery_type' => 'memory', // memory, consul, etcd, redis
            'consul_host' => 'localhost',
            'consul_port' => 8500,
            'etcd_host' => 'localhost',
            'etcd_port' => 2379,
            'redis_host' => 'localhost',
            'redis_port' => 6379,
            'cache_ttl' => 300, // 5 minutes
            'health_check_interval' => 30,
            'retry_attempts' => 3,
            'timeout' => 5
        ], $config);

        $this->initializeDiscoveryClient();
    }

    /**
     * Enregistre un service
     *
     * @param string $name Nom du service
     * @param array $config Configuration du service
     * @return bool
     */
    public function register(string $name, array $config): bool
    {
        $serviceConfig = array_merge([
            'host' => 'localhost',
            'port' => 8080,
            'protocol' => 'http',
            'version' => '1.0.0',
            'health_check' => '/health',
            'tags' => [],
            'metadata' => []
        ], $config);

        $serviceId = $this->generateServiceId($name, $serviceConfig);
        $service = [
            'id' => $serviceId,
            'name' => $name,
            'config' => $serviceConfig,
            'registered_at' => time(),
            'last_health_check' => null,
            'status' => 'healthy'
        ];

        // Enregistrer localement
        $this->services[$serviceId] = $service;

        // Enregistrer dans le système de découverte externe
        if ($this->discoveryClient) {
            return $this->registerWithDiscovery($service);
        }

        return true;
    }

    /**
     * Désenregistre un service
     *
     * @param string $serviceId ID du service
     * @return bool
     */
    public function deregister(string $serviceId): bool
    {
        if (isset($this->services[$serviceId])) {
            unset($this->services[$serviceId]);
        }

        // Supprimer du cache
        $this->clearServiceCache($serviceId);

        // Désenregistrer du système de découverte externe
        if ($this->discoveryClient) {
            return $this->deregisterFromDiscovery($serviceId);
        }

        return true;
    }

    /**
     * Découvre un service par nom
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres optionnels
     * @return array|null
     */
    public function discover(string $serviceName, array $filters = []): ?array
    {
        // Vérifier le cache d'abord
        $cacheKey = $this->getCacheKey($serviceName, $filters);
        if (isset($this->serviceCache[$cacheKey])) {
            $cached = $this->serviceCache[$cacheKey];
            if (time() - $cached['cached_at'] < $this->config['cache_ttl']) {
                return $cached['service'];
            }
        }

        $service = null;

        // Découverte via le système externe
        if ($this->discoveryClient) {
            $service = $this->discoverFromExternal($serviceName, $filters);
        }

        // Fallback vers les services locaux
        if (!$service) {
            $service = $this->discoverFromLocal($serviceName, $filters);
        }

        // Mettre en cache le résultat
        if ($service) {
            $this->serviceCache[$cacheKey] = [
                'service' => $service,
                'cached_at' => time()
            ];
        }

        return $service;
    }

    /**
     * Découvre tous les services d'un type
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres optionnels
     * @return array
     */
    public function discoverAll(string $serviceName, array $filters = []): array
    {
        $services = [];

        // Découverte via le système externe
        if ($this->discoveryClient) {
            $services = array_merge($services, $this->discoverAllFromExternal($serviceName, $filters));
        }

        // Ajouter les services locaux
        $localServices = $this->discoverAllFromLocal($serviceName, $filters);
        $services = array_merge($services, $localServices);

        // Supprimer les doublons
        $uniqueServices = [];
        foreach ($services as $service) {
            $uniqueServices[$service['id']] = $service;
        }

        return array_values($uniqueServices);
    }

    /**
     * Vérifie la santé d'un service
     *
     * @param string $serviceId ID du service
     * @return bool
     */
    public function healthCheck(string $serviceId): bool
    {
        if (!isset($this->services[$serviceId])) {
            return false;
        }

        $service = $this->services[$serviceId];
        $config = $service['config'];
        
        $url = $this->buildServiceUrl($config) . $config['health_check'];
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => $this->config['timeout'],
                    'method' => 'GET'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            $healthy = $response !== false;
            
            // Mettre à jour le statut
            $this->services[$serviceId]['last_health_check'] = time();
            $this->services[$serviceId]['status'] = $healthy ? 'healthy' : 'unhealthy';
            
            return $healthy;
            
        } catch (\Exception $e) {
            $this->services[$serviceId]['status'] = 'unhealthy';
            return false;
        }
    }

    /**
     * Vérifie la santé de tous les services
     *
     * @return array
     */
    public function healthCheckAll(): array
    {
        $results = [];
        
        foreach ($this->services as $serviceId => $service) {
            $results[$serviceId] = [
                'name' => $service['name'],
                'healthy' => $this->healthCheck($serviceId),
                'last_check' => $service['last_health_check']
            ];
        }
        
        return $results;
    }

    /**
     * Obtient la liste de tous les services enregistrés
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Obtient un service par ID
     *
     * @param string $serviceId ID du service
     * @return array|null
     */
    public function getService(string $serviceId): ?array
    {
        return $this->services[$serviceId] ?? null;
    }

    /**
     * Initialise le client de découverte de services
     *
     * @return void
     */
    protected function initializeDiscoveryClient(): void
    {
        switch ($this->config['discovery_type']) {
            case 'consul':
                $this->initializeConsulClient();
                break;
            case 'etcd':
                $this->initializeEtcdClient();
                break;
            case 'redis':
                $this->initializeRedisClient();
                break;
            default:
                // Utiliser la mémoire locale uniquement
                $this->discoveryClient = null;
                break;
        }
    }

    /**
     * Initialise le client Consul
     *
     * @return void
     */
    protected function initializeConsulClient(): void
    {
        // Implémentation du client Consul
        // Cette méthode devrait initialiser un client Consul réel
        $this->discoveryClient = new \stdClass(); // Placeholder
    }

    /**
     * Initialise le client etcd
     *
     * @return void
     */
    protected function initializeEtcdClient(): void
    {
        // Implémentation du client etcd
        // Cette méthode devrait initialiser un client etcd réel
        $this->discoveryClient = new \stdClass(); // Placeholder
    }

    /**
     * Initialise le client Redis
     *
     * @return void
     */
    protected function initializeRedisClient(): void
    {
        // Implémentation du client Redis
        // Cette méthode devrait initialiser un client Redis réel
        $this->discoveryClient = new \stdClass(); // Placeholder
    }

    /**
     * Enregistre un service avec le système de découverte externe
     *
     * @param array $service Service à enregistrer
     * @return bool
     */
    protected function registerWithDiscovery(array $service): bool
    {
        // Implémentation spécifique selon le type de découverte
        switch ($this->config['discovery_type']) {
            case 'consul':
                return $this->registerWithConsul($service);
            case 'etcd':
                return $this->registerWithEtcd($service);
            case 'redis':
                return $this->registerWithRedis($service);
            default:
                return true;
        }
    }

    /**
     * Désenregistre un service du système de découverte externe
     *
     * @param string $serviceId ID du service
     * @return bool
     */
    protected function deregisterFromDiscovery(string $serviceId): bool
    {
        // Implémentation spécifique selon le type de découverte
        switch ($this->config['discovery_type']) {
            case 'consul':
                return $this->deregisterFromConsul($serviceId);
            case 'etcd':
                return $this->deregisterFromEtcd($serviceId);
            case 'redis':
                return $this->deregisterFromRedis($serviceId);
            default:
                return true;
        }
    }

    /**
     * Découvre un service depuis le système externe
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres
     * @return array|null
     */
    protected function discoverFromExternal(string $serviceName, array $filters): ?array
    {
        // Implémentation spécifique selon le type de découverte
        switch ($this->config['discovery_type']) {
            case 'consul':
                return $this->discoverFromConsul($serviceName, $filters);
            case 'etcd':
                return $this->discoverFromEtcd($serviceName, $filters);
            case 'redis':
                return $this->discoverFromRedis($serviceName, $filters);
            default:
                return null;
        }
    }

    /**
     * Découvre un service depuis les services locaux
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres
     * @return array|null
     */
    protected function discoverFromLocal(string $serviceName, array $filters): ?array
    {
        foreach ($this->services as $service) {
            if ($service['name'] === $serviceName && $this->matchesFilters($service, $filters)) {
                return $service;
            }
        }
        
        return null;
    }

    /**
     * Découvre tous les services depuis le système externe
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres
     * @return array
     */
    protected function discoverAllFromExternal(string $serviceName, array $filters): array
    {
        // Implémentation spécifique selon le type de découverte
        switch ($this->config['discovery_type']) {
            case 'consul':
                return $this->discoverAllFromConsul($serviceName, $filters);
            case 'etcd':
                return $this->discoverAllFromEtcd($serviceName, $filters);
            case 'redis':
                return $this->discoverAllFromRedis($serviceName, $filters);
            default:
                return [];
        }
    }

    /**
     * Découvre tous les services depuis les services locaux
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres
     * @return array
     */
    protected function discoverAllFromLocal(string $serviceName, array $filters): array
    {
        $matchingServices = [];
        
        foreach ($this->services as $service) {
            if ($service['name'] === $serviceName && $this->matchesFilters($service, $filters)) {
                $matchingServices[] = $service;
            }
        }
        
        return $matchingServices;
    }

    /**
     * Vérifie si un service correspond aux filtres
     *
     * @param array $service Service à vérifier
     * @param array $filters Filtres à appliquer
     * @return bool
     */
    protected function matchesFilters(array $service, array $filters): bool
    {
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'version':
                    if ($service['config']['version'] !== $value) {
                        return false;
                    }
                    break;
                case 'tags':
                    $serviceTags = $service['config']['tags'] ?? [];
                    if (!array_intersect($value, $serviceTags)) {
                        return false;
                    }
                    break;
                case 'status':
                    if ($service['status'] !== $value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }

    /**
     * Génère un ID unique pour un service
     *
     * @param string $name Nom du service
     * @param array $config Configuration du service
     * @return string
     */
    protected function generateServiceId(string $name, array $config): string
    {
        return $name . '-' . $config['host'] . '-' . $config['port'] . '-' . uniqid();
    }

    /**
     * Construit l'URL d'un service
     *
     * @param array $config Configuration du service
     * @return string
     */
    protected function buildServiceUrl(array $config): string
    {
        return $config['protocol'] . '://' . $config['host'] . ':' . $config['port'];
    }

    /**
     * Génère une clé de cache
     *
     * @param string $serviceName Nom du service
     * @param array $filters Filtres
     * @return string
     */
    protected function getCacheKey(string $serviceName, array $filters): string
    {
        return md5($serviceName . serialize($filters));
    }

    /**
     * Vide le cache d'un service
     *
     * @param string $serviceId ID du service
     * @return void
     */
    protected function clearServiceCache(string $serviceId): void
    {
        // Supprimer toutes les entrées de cache liées à ce service
        foreach ($this->serviceCache as $key => $cached) {
            if (isset($cached['service']['id']) && $cached['service']['id'] === $serviceId) {
                unset($this->serviceCache[$key]);
            }
        }
    }

    // Méthodes placeholder pour les implémentations spécifiques
    // Ces méthodes devraient être implémentées selon les besoins réels
    
    protected function registerWithConsul(array $service): bool { return true; }
    protected function deregisterFromConsul(string $serviceId): bool { return true; }
    protected function discoverFromConsul(string $serviceName, array $filters): ?array { return null; }
    protected function discoverAllFromConsul(string $serviceName, array $filters): array { return []; }
    
    protected function registerWithEtcd(array $service): bool { return true; }
    protected function deregisterFromEtcd(string $serviceId): bool { return true; }
    protected function discoverFromEtcd(string $serviceName, array $filters): ?array { return null; }
    protected function discoverAllFromEtcd(string $serviceName, array $filters): array { return []; }
    
    protected function registerWithRedis(array $service): bool { return true; }
    protected function deregisterFromRedis(string $serviceId): bool { return true; }
    protected function discoverFromRedis(string $serviceName, array $filters): ?array { return null; }
    protected function discoverAllFromRedis(string $serviceName, array $filters): array { return []; }
}