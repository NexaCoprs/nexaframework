<?php

namespace Nexa\Microservices;

/**
 * Client pour les appels entre microservices
 * 
 * Cette classe gère les appels HTTP entre services avec circuit breaker,
 * retry logic, et load balancing.
 * 
 * @package Nexa\Microservices
 */
class ServiceClient
{
    /**
     * Registre de services
     *
     * @var ServiceRegistry
     */
    protected $serviceRegistry;

    /**
     * Configuration du client
     *
     * @var array
     */
    protected $config;

    /**
     * Circuit breakers par service
     *
     * @var array
     */
    protected $circuitBreakers = [];

    /**
     * Métriques des appels
     *
     * @var array
     */
    protected $metrics = [];

    /**
     * Logger
     *
     * @var mixed
     */
    protected $logger;

    /**
     * Constructeur
     *
     * @param ServiceRegistry $serviceRegistry
     * @param array $config
     */
    public function __construct(ServiceRegistry $serviceRegistry, array $config = [])
    {
        $this->serviceRegistry = $serviceRegistry;
        $this->config = array_merge([
            'timeout' => 30,
            'connect_timeout' => 5,
            'retry_attempts' => 3,
            'retry_delay' => 1000, // millisecondes
            'circuit_breaker' => [
                'failure_threshold' => 5,
                'recovery_timeout' => 60,
                'success_threshold' => 3
            ],
            'load_balancer' => 'round_robin', // round_robin, random, least_connections
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'user_agent' => 'Nexa-ServiceClient/1.0'
        ], $config);
    }

    /**
     * Effectue un appel GET vers un service
     *
     * @param string $serviceName Nom du service
     * @param string $path Chemin de l'endpoint
     * @param array $params Paramètres de requête
     * @param array $options Options supplémentaires
     * @return array
     */
    public function get(string $serviceName, string $path, array $params = [], array $options = []): array
    {
        return $this->request($serviceName, 'GET', $path, $params, [], $options);
    }

    /**
     * Effectue un appel POST vers un service
     *
     * @param string $serviceName Nom du service
     * @param string $path Chemin de l'endpoint
     * @param array $data Données à envoyer
     * @param array $options Options supplémentaires
     * @return array
     */
    public function post(string $serviceName, string $path, array $data = [], array $options = []): array
    {
        return $this->request($serviceName, 'POST', $path, [], $data, $options);
    }

    /**
     * Effectue un appel PUT vers un service
     *
     * @param string $serviceName Nom du service
     * @param string $path Chemin de l'endpoint
     * @param array $data Données à envoyer
     * @param array $options Options supplémentaires
     * @return array
     */
    public function put(string $serviceName, string $path, array $data = [], array $options = []): array
    {
        return $this->request($serviceName, 'PUT', $path, [], $data, $options);
    }

    /**
     * Effectue un appel DELETE vers un service
     *
     * @param string $serviceName Nom du service
     * @param string $path Chemin de l'endpoint
     * @param array $options Options supplémentaires
     * @return array
     */
    public function delete(string $serviceName, string $path, array $options = []): array
    {
        return $this->request($serviceName, 'DELETE', $path, [], [], $options);
    }

    /**
     * Effectue une requête vers un service
     *
     * @param string $serviceName Nom du service
     * @param string $method Méthode HTTP
     * @param string $path Chemin de l'endpoint
     * @param array $params Paramètres de requête
     * @param array $data Données à envoyer
     * @param array $options Options supplémentaires
     * @return array
     * @throws \Exception
     */
    public function request(string $serviceName, string $method, string $path, array $params = [], array $data = [], array $options = []): array
    {
        $startTime = microtime(true);
        
        try {
            // Vérifier le circuit breaker
            if (!$this->isCircuitBreakerOpen($serviceName)) {
                throw new \Exception("Circuit breaker is open for service: {$serviceName}");
            }

            // Découvrir le service
            $service = $this->discoverService($serviceName, $options);
            if (!$service) {
                throw new \Exception("Service not found: {$serviceName}");
            }

            // Effectuer la requête avec retry
            $response = $this->executeWithRetry($service, $method, $path, $params, $data, $options);
            
            // Enregistrer le succès
            $this->recordSuccess($serviceName, microtime(true) - $startTime);
            
            return $response;
            
        } catch (\Exception $e) {
            // Enregistrer l'échec
            $this->recordFailure($serviceName, microtime(true) - $startTime, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécute une requête avec retry logic
     *
     * @param array $service Configuration du service
     * @param string $method Méthode HTTP
     * @param string $path Chemin
     * @param array $params Paramètres
     * @param array $data Données
     * @param array $options Options
     * @return array
     * @throws \Exception
     */
    protected function executeWithRetry(array $service, string $method, string $path, array $params, array $data, array $options): array
    {
        $attempts = 0;
        $maxAttempts = $options['retry_attempts'] ?? $this->config['retry_attempts'];
        $retryDelay = $options['retry_delay'] ?? $this->config['retry_delay'];
        
        while ($attempts < $maxAttempts) {
            try {
                return $this->executeRequest($service, $method, $path, $params, $data, $options);
            } catch (\Exception $e) {
                $attempts++;
                
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                
                // Attendre avant de réessayer
                usleep($retryDelay * 1000);
                
                // Augmenter le délai pour le prochain essai (backoff exponentiel)
                $retryDelay *= 2;
                
                $this->log('warning', "Retry attempt {$attempts} for service {$service['name']}: {$e->getMessage()}");
            }
        }
        
        throw new \Exception('Max retry attempts reached');
    }

    /**
     * Exécute une requête HTTP
     *
     * @param array $service Configuration du service
     * @param string $method Méthode HTTP
     * @param string $path Chemin
     * @param array $params Paramètres
     * @param array $data Données
     * @param array $options Options
     * @return array
     * @throws \Exception
     */
    protected function executeRequest(array $service, string $method, string $path, array $params, array $data, array $options): array
    {
        $url = $this->buildUrl($service, $path, $params);
        $headers = array_merge($this->config['headers'], $options['headers'] ?? []);
        
        // Ajouter l'en-tête de traçage si disponible
        if (isset($options['trace_id'])) {
            $headers['X-Trace-ID'] = $options['trace_id'];
        }
        
        $context = $this->buildHttpContext($method, $headers, $data, $options);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            throw new \Exception("HTTP request failed: " . ($error['message'] ?? 'Unknown error'));
        }
        
        // Analyser les en-têtes de réponse
        $responseHeaders = $this->parseResponseHeaders($http_response_header ?? []);
        $statusCode = $responseHeaders['status_code'] ?? 200;
        
        if ($statusCode >= 400) {
            throw new \Exception("HTTP error {$statusCode}: {$response}");
        }
        
        // Décoder la réponse JSON
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON response: " . json_last_error_msg());
        }
        
        return [
            'data' => $decodedResponse,
            'headers' => $responseHeaders,
            'status_code' => $statusCode
        ];
    }

    /**
     * Découvre un service avec load balancing
     *
     * @param string $serviceName Nom du service
     * @param array $options Options
     * @return array|null
     */
    protected function discoverService(string $serviceName, array $options): ?array
    {
        $services = $this->serviceRegistry->discoverAll($serviceName, $options['filters'] ?? []);
        
        if (empty($services)) {
            return null;
        }
        
        // Filtrer les services sains
        $healthyServices = array_filter($services, fn($service) => $service['status'] === 'healthy');
        
        if (empty($healthyServices)) {
            // Utiliser tous les services si aucun n'est marqué comme sain
            $healthyServices = $services;
        }
        
        // Appliquer le load balancing
        return $this->selectService($healthyServices, $options);
    }

    /**
     * Sélectionne un service selon la stratégie de load balancing
     *
     * @param array $services Services disponibles
     * @param array $options Options
     * @return array
     */
    protected function selectService(array $services, array $options): array
    {
        $strategy = $options['load_balancer'] ?? $this->config['load_balancer'];
        
        switch ($strategy) {
            case 'random':
                return $services[array_rand($services)];
                
            case 'least_connections':
                // Sélectionner le service avec le moins de connexions actives
                usort($services, function($a, $b) {
                    $connectionsA = $this->getActiveConnections($a['id']);
                    $connectionsB = $this->getActiveConnections($b['id']);
                    return $connectionsA <=> $connectionsB;
                });
                return $services[0];
                
            case 'round_robin':
            default:
                // Implémentation simple du round robin
                static $roundRobinCounters = [];
                $serviceName = $services[0]['name'];
                
                if (!isset($roundRobinCounters[$serviceName])) {
                    $roundRobinCounters[$serviceName] = 0;
                }
                
                $index = $roundRobinCounters[$serviceName] % count($services);
                $roundRobinCounters[$serviceName]++;
                
                return $services[$index];
        }
    }

    /**
     * Vérifie si le circuit breaker est ouvert
     *
     * @param string $serviceName Nom du service
     * @return bool
     */
    protected function isCircuitBreakerOpen(string $serviceName): bool
    {
        if (!isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName] = [
                'state' => 'closed', // closed, open, half_open
                'failure_count' => 0,
                'last_failure_time' => null,
                'success_count' => 0
            ];
        }
        
        $breaker = &$this->circuitBreakers[$serviceName];
        $config = $this->config['circuit_breaker'];
        
        switch ($breaker['state']) {
            case 'open':
                // Vérifier si le timeout de récupération est écoulé
                if (time() - $breaker['last_failure_time'] >= $config['recovery_timeout']) {
                    $breaker['state'] = 'half_open';
                    $breaker['success_count'] = 0;
                    return true;
                }
                return false;
                
            case 'half_open':
                return true;
                
            case 'closed':
            default:
                return true;
        }
    }

    /**
     * Enregistre un succès d'appel
     *
     * @param string $serviceName Nom du service
     * @param float $responseTime Temps de réponse
     * @return void
     */
    protected function recordSuccess(string $serviceName, float $responseTime): void
    {
        // Mettre à jour le circuit breaker
        if (isset($this->circuitBreakers[$serviceName])) {
            $breaker = &$this->circuitBreakers[$serviceName];
            
            if ($breaker['state'] === 'half_open') {
                $breaker['success_count']++;
                if ($breaker['success_count'] >= $this->config['circuit_breaker']['success_threshold']) {
                    $breaker['state'] = 'closed';
                    $breaker['failure_count'] = 0;
                }
            } else {
                $breaker['failure_count'] = 0;
            }
        }
        
        // Enregistrer les métriques
        $this->recordMetrics($serviceName, 'success', $responseTime);
    }

    /**
     * Enregistre un échec d'appel
     *
     * @param string $serviceName Nom du service
     * @param float $responseTime Temps de réponse
     * @param string $error Message d'erreur
     * @return void
     */
    protected function recordFailure(string $serviceName, float $responseTime, string $error): void
    {
        // Mettre à jour le circuit breaker
        if (!isset($this->circuitBreakers[$serviceName])) {
            $this->circuitBreakers[$serviceName] = [
                'state' => 'closed',
                'failure_count' => 0,
                'last_failure_time' => null,
                'success_count' => 0
            ];
        }
        
        $breaker = &$this->circuitBreakers[$serviceName];
        $breaker['failure_count']++;
        $breaker['last_failure_time'] = time();
        
        if ($breaker['failure_count'] >= $this->config['circuit_breaker']['failure_threshold']) {
            $breaker['state'] = 'open';
        }
        
        // Enregistrer les métriques
        $this->recordMetrics($serviceName, 'failure', $responseTime, $error);
    }

    /**
     * Enregistre les métriques d'appel
     *
     * @param string $serviceName Nom du service
     * @param string $status Statut (success/failure)
     * @param float $responseTime Temps de réponse
     * @param string|null $error Message d'erreur
     * @return void
     */
    protected function recordMetrics(string $serviceName, string $status, float $responseTime, ?string $error = null): void
    {
        if (!isset($this->metrics[$serviceName])) {
            $this->metrics[$serviceName] = [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_response_time' => 0,
                'min_response_time' => null,
                'max_response_time' => null,
                'last_request_time' => null
            ];
        }
        
        $metrics = &$this->metrics[$serviceName];
        $metrics['total_requests']++;
        $metrics['total_response_time'] += $responseTime;
        $metrics['last_request_time'] = time();
        
        if ($status === 'success') {
            $metrics['successful_requests']++;
        } else {
            $metrics['failed_requests']++;
        }
        
        if ($metrics['min_response_time'] === null || $responseTime < $metrics['min_response_time']) {
            $metrics['min_response_time'] = $responseTime;
        }
        
        if ($metrics['max_response_time'] === null || $responseTime > $metrics['max_response_time']) {
            $metrics['max_response_time'] = $responseTime;
        }
    }

    /**
     * Construit l'URL complète pour un service
     *
     * @param array $service Configuration du service
     * @param string $path Chemin
     * @param array $params Paramètres de requête
     * @return string
     */
    protected function buildUrl(array $service, string $path, array $params): string
    {
        $config = $service['config'];
        $baseUrl = $config['protocol'] . '://' . $config['host'] . ':' . $config['port'];
        
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Construit le contexte HTTP pour la requête
     *
     * @param string $method Méthode HTTP
     * @param array $headers En-têtes
     * @param array $data Données
     * @param array $options Options
     * @return resource
     */
    protected function buildHttpContext(string $method, array $headers, array $data, array $options)
    {
        $contextOptions = [
            'http' => [
                'method' => $method,
                'timeout' => $options['timeout'] ?? $this->config['timeout'],
                'user_agent' => $this->config['user_agent'],
                'ignore_errors' => true
            ]
        ];
        
        // Ajouter les en-têtes
        $headerStrings = [];
        foreach ($headers as $name => $value) {
            $headerStrings[] = "{$name}: {$value}";
        }
        $contextOptions['http']['header'] = implode("\r\n", $headerStrings);
        
        // Ajouter le contenu pour POST/PUT
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            $contextOptions['http']['content'] = json_encode($data);
        }
        
        return stream_context_create($contextOptions);
    }

    /**
     * Parse les en-têtes de réponse HTTP
     *
     * @param array $headers En-têtes bruts
     * @return array
     */
    protected function parseResponseHeaders(array $headers): array
    {
        $parsed = [];
        
        foreach ($headers as $header) {
            if (strpos($header, 'HTTP/') === 0) {
                // Ligne de statut
                $parts = explode(' ', $header, 3);
                $parsed['status_code'] = (int)($parts[1] ?? 200);
                $parsed['status_message'] = $parts[2] ?? '';
            } else {
                // En-tête normal
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $parsed[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
            }
        }
        
        return $parsed;
    }

    /**
     * Obtient le nombre de connexions actives pour un service
     *
     * @param string $serviceId ID du service
     * @return int
     */
    protected function getActiveConnections(string $serviceId): int
    {
        // Implémentation simplifiée
        // Dans une vraie implémentation, ceci devrait tracker les connexions actives
        return 0;
    }

    /**
     * Obtient les métriques d'un service
     *
     * @param string $serviceName Nom du service
     * @return array|null
     */
    public function getMetrics(string $serviceName): ?array
    {
        if (!isset($this->metrics[$serviceName])) {
            return null;
        }
        
        $metrics = $this->metrics[$serviceName];
        
        return [
            'service_name' => $serviceName,
            'total_requests' => $metrics['total_requests'],
            'successful_requests' => $metrics['successful_requests'],
            'failed_requests' => $metrics['failed_requests'],
            'success_rate' => $metrics['total_requests'] > 0 ? 
                ($metrics['successful_requests'] / $metrics['total_requests']) * 100 : 0,
            'average_response_time' => $metrics['total_requests'] > 0 ? 
                $metrics['total_response_time'] / $metrics['total_requests'] : 0,
            'min_response_time' => $metrics['min_response_time'],
            'max_response_time' => $metrics['max_response_time'],
            'last_request_time' => $metrics['last_request_time']
        ];
    }

    /**
     * Obtient l'état du circuit breaker
     *
     * @param string $serviceName Nom du service
     * @return array|null
     */
    public function getCircuitBreakerState(string $serviceName): ?array
    {
        return $this->circuitBreakers[$serviceName] ?? null;
    }

    /**
     * Définit le logger
     *
     * @param mixed $logger
     * @return void
     */
    public function setLogger($logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Enregistre un message de log
     *
     * @param string $level Niveau de log
     * @param string $message Message
     * @param array $context Contexte
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}