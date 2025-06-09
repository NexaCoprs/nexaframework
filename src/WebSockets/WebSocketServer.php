<?php

namespace Nexa\WebSockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

use SplObjectStorage;

/**
 * Serveur WebSocket pour le framework Nexa
 * 
 * Cette classe gère les connexions WebSocket et la communication en temps réel.
 * 
 * @package Nexa\WebSockets
 */
class WebSocketServer implements MessageComponentInterface
{
    /**
     * Connexions actives
     *
     * @var SplObjectStorage
     */
    protected $connections;

    /**
     * Canaux de communication
     *
     * @var array
     */
    protected $channels = [];

    /**
     * Gestionnaires d'événements
     *
     * @var array
     */
    protected $eventHandlers = [];

    /**
     * Configuration du serveur
     *
     * @var array
     */
    protected $config;

    /**
     * Middleware d'authentification
     *
     * @var callable|null
     */
    protected $authMiddleware;

    /**
     * Logger
     *
     * @var mixed
     */
    protected $logger;

    /**
     * État du serveur
     *
     * @var bool
     */
    protected $running = false;

    /**
     * Constructeur
     *
     * @param string $host Adresse IP du serveur
     * @param int $port Port du serveur
     * @param array $config Configuration du serveur
     */
    public function __construct(string $host = '0.0.0.0', int $port = 8080, array $config = [])
    {
        $this->connections = new SplObjectStorage();
        $this->config = array_merge([
            'host' => $host,
            'port' => $port,
            'max_connections' => 1000,
            'heartbeat_interval' => 30,
            'auth_required' => false,
            'cors_allowed_origins' => ['*'],
            'compression' => true,
            'logging' => true
        ], $config);
    }

    /**
     * Démarre le serveur WebSocket
     *
     * @return void
     */
    public function start(): void
    {
        $server = IoServer::factory(
            new HttpServer(
                new WsServer($this)
            ),
            $this->config['port'],
            $this->config['host']
        );

        $this->running = true;
        $this->log('info', "WebSocket server started on {$this->config['host']}:{$this->config['port']}");
        
        // Démarrer le heartbeat si configuré
        if ($this->config['heartbeat_interval'] > 0) {
            $this->startHeartbeat();
        }

        $server->run();
    }

    /**
     * Nouvelle connexion WebSocket
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        // Vérifier la limite de connexions
        if (count($this->connections) >= $this->config['max_connections']) {
            $this->log('warning', "Connection limit reached, rejecting connection {$conn->resourceId}");
            $conn->close();
            return;
        }

        $this->connections->attach($conn, [
            'id' => $conn->resourceId,
            'authenticated' => false,
            'user' => null,
            'channels' => [],
            'connected_at' => time(),
            'last_ping' => time()
        ]);

        $this->log('info', "New connection: {$conn->resourceId}");
        
        // Envoyer un message de bienvenue
        $this->sendToConnection($conn, [
            'type' => 'connection.established',
            'data' => [
                'connection_id' => $conn->resourceId,
                'server_time' => time()
            ]
        ]);

        $this->fireEvent('connection.opened', $conn);
    }

    /**
     * Message reçu d'une connexion WebSocket
     *
     * @param ConnectionInterface $from
     * @param string $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        try {
            $data = json_decode($msg, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendError($from, 'Invalid JSON format');
                return;
            }

            $this->updateLastPing($from);
            $this->handleMessage($from, $data);
            
        } catch (\Exception $e) {
            $this->log('error', "Error handling message: {$e->getMessage()}");
            $this->sendError($from, 'Internal server error');
        }
    }

    /**
     * Connexion fermée
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $connectionData = $this->connections[$conn] ?? null;
        
        if ($connectionData) {
            // Quitter tous les canaux
            foreach ($connectionData['channels'] as $channel) {
                $this->leaveChannel($conn, $channel);
            }
        }

        $this->connections->detach($conn);
        $this->log('info', "Connection closed: {$conn->resourceId}");
        $this->fireEvent('connection.closed', $conn);
    }

    /**
     * Erreur de connexion
     *
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->log('error', "Connection error {$conn->resourceId}: {$e->getMessage()}");
        $conn->close();
    }

    /**
     * Gère un message reçu
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleMessage(ConnectionInterface $conn, array $data): void
    {
        $type = $data['type'] ?? null;
        $payload = $data['data'] ?? [];

        switch ($type) {
            case 'auth':
                $this->handleAuth($conn, $payload);
                break;
                
            case 'join_channel':
                $this->handleJoinChannel($conn, $payload);
                break;
                
            case 'leave_channel':
                $this->handleLeaveChannel($conn, $payload);
                break;
                
            case 'message':
                $this->handleChannelMessage($conn, $payload);
                break;
                
            case 'ping':
                $this->handlePing($conn, $payload);
                break;
                
            default:
                $this->fireEvent('message.received', $conn, $data);
                break;
        }
    }

    /**
     * Gère l'authentification
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleAuth(ConnectionInterface $conn, array $data): void
    {
        if (!$this->authMiddleware) {
            $this->sendError($conn, 'Authentication not configured');
            return;
        }

        $token = $data['token'] ?? null;
        if (!$token) {
            $this->sendError($conn, 'Token required');
            return;
        }

        try {
            $user = call_user_func($this->authMiddleware, $token);
            
            if ($user) {
                $connectionData = $this->connections[$conn];
                $connectionData['authenticated'] = true;
                $connectionData['user'] = $user;
                $this->connections[$conn] = $connectionData;

                $this->sendToConnection($conn, [
                    'type' => 'auth.success',
                    'data' => ['user' => $user]
                ]);
                
                $this->fireEvent('user.authenticated', $conn, $user);
            } else {
                $this->sendError($conn, 'Invalid token');
            }
        } catch (\Exception $e) {
            $this->sendError($conn, 'Authentication failed');
        }
    }

    /**
     * Gère la demande de rejoindre un canal
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleJoinChannel(ConnectionInterface $conn, array $data): void
    {
        $channel = $data['channel'] ?? null;
        if (!$channel) {
            $this->sendError($conn, 'Channel name required');
            return;
        }

        if ($this->canJoinChannel($conn, $channel)) {
            $this->joinChannel($conn, $channel);
        } else {
            $this->sendError($conn, 'Access denied to channel');
        }
    }

    /**
     * Gère la demande de quitter un canal
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleLeaveChannel(ConnectionInterface $conn, array $data): void
    {
        $channel = $data['channel'] ?? null;
        if (!$channel) {
            $this->sendError($conn, 'Channel name required');
            return;
        }

        $this->leaveChannel($conn, $channel);
    }

    /**
     * Gère un message de canal
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handleChannelMessage(ConnectionInterface $conn, array $data): void
    {
        $channel = $data['channel'] ?? null;
        $message = $data['message'] ?? null;

        if (!$channel || !$message) {
            $this->sendError($conn, 'Channel and message required');
            return;
        }

        if ($this->isInChannel($conn, $channel)) {
            $this->broadcastToChannel($channel, [
                'type' => 'channel.message',
                'data' => [
                    'channel' => $channel,
                    'message' => $message,
                    'from' => $this->connections[$conn]['user'] ?? $conn->resourceId,
                    'timestamp' => time()
                ]
            ], $conn);
        } else {
            $this->sendError($conn, 'Not in channel');
        }
    }

    /**
     * Gère un ping
     *
     * @param ConnectionInterface $conn
     * @param array $data
     * @return void
     */
    protected function handlePing(ConnectionInterface $conn, array $data): void
    {
        $this->sendToConnection($conn, [
            'type' => 'pong',
            'data' => $data
        ]);
    }

    /**
     * Vérifie si une connexion peut rejoindre un canal
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return bool
     */
    protected function canJoinChannel(ConnectionInterface $conn, string $channel): bool
    {
        // Logique d'autorisation par défaut
        // Les canaux privés nécessitent une authentification
        if (str_starts_with($channel, 'private.')) {
            return $this->connections[$conn]['authenticated'] ?? false;
        }

        return true;
    }

    /**
     * Fait rejoindre une connexion à un canal
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return void
     */
    protected function joinChannel(ConnectionInterface $conn, string $channel): void
    {
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = new SplObjectStorage();
        }

        $this->channels[$channel]->attach($conn);
        
        $connectionData = $this->connections[$conn];
        $connectionData['channels'][] = $channel;
        $this->connections[$conn] = $connectionData;

        $this->sendToConnection($conn, [
            'type' => 'channel.joined',
            'data' => ['channel' => $channel]
        ]);

        $this->fireEvent('channel.joined', $conn, $channel);
    }

    /**
     * Fait quitter une connexion d'un canal
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return void
     */
    protected function leaveChannel(ConnectionInterface $conn, string $channel): void
    {
        if (isset($this->channels[$channel])) {
            $this->channels[$channel]->detach($conn);
            
            if (count($this->channels[$channel]) === 0) {
                unset($this->channels[$channel]);
            }
        }

        $connectionData = $this->connections[$conn];
        $connectionData['channels'] = array_filter(
            $connectionData['channels'],
            fn($c) => $c !== $channel
        );
        $this->connections[$conn] = $connectionData;

        $this->sendToConnection($conn, [
            'type' => 'channel.left',
            'data' => ['channel' => $channel]
        ]);

        $this->fireEvent('channel.left', $conn, $channel);
    }

    /**
     * Vérifie si une connexion est dans un canal
     *
     * @param ConnectionInterface $conn
     * @param string $channel
     * @return bool
     */
    protected function isInChannel(ConnectionInterface $conn, string $channel): bool
    {
        return isset($this->channels[$channel]) && $this->channels[$channel]->contains($conn);
    }

    /**
     * Diffuse un message à toutes les connexions d'un canal
     *
     * @param string $channel
     * @param array $message
     * @param ConnectionInterface|null $except
     * @return void
     */
    public function broadcastToChannel(string $channel, array $message, ConnectionInterface $except = null): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }

        foreach ($this->channels[$channel] as $conn) {
            if ($except && $conn === $except) {
                continue;
            }
            
            $this->sendToConnection($conn, $message);
        }
    }

    /**
     * Envoie un message à une connexion spécifique
     *
     * @param ConnectionInterface $conn
     * @param array $message
     * @return void
     */
    public function sendToConnection(ConnectionInterface $conn, array $message): void
    {
        try {
            $conn->send(json_encode($message));
        } catch (\Exception $e) {
            $this->log('error', "Failed to send message to connection {$conn->resourceId}: {$e->getMessage()}");
        }
    }

    /**
     * Envoie une erreur à une connexion
     *
     * @param ConnectionInterface $conn
     * @param string $error
     * @return void
     */
    protected function sendError(ConnectionInterface $conn, string $error): void
    {
        $this->sendToConnection($conn, [
            'type' => 'error',
            'data' => ['message' => $error]
        ]);
    }

    /**
     * Met à jour le timestamp du dernier ping
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    protected function updateLastPing(ConnectionInterface $conn): void
    {
        $connectionData = $this->connections[$conn];
        $connectionData['last_ping'] = time();
        $this->connections[$conn] = $connectionData;
    }

    /**
     * Démarre le système de heartbeat
     *
     * @return void
     */
    protected function startHeartbeat(): void
    {
        // Implémentation du heartbeat pour vérifier les connexions inactives
        // Cette méthode devrait être appelée périodiquement
    }

    /**
     * Définit le middleware d'authentification
     *
     * @param callable $middleware
     * @return void
     */
    public function setAuthMiddleware(callable $middleware): void
    {
        $this->authMiddleware = $middleware;
    }

    /**
     * Ajoute un gestionnaire d'événement
     *
     * @param string $event
     * @param callable $handler
     * @return void
     */
    public function on(string $event, callable $handler): void
    {
        if (!isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event] = [];
        }
        
        $this->eventHandlers[$event][] = $handler;
    }

    /**
     * Déclenche un événement
     *
     * @param string $event
     * @param mixed ...$args
     * @return void
     */
    protected function fireEvent(string $event, ...$args): void
    {
        if (isset($this->eventHandlers[$event])) {
            foreach ($this->eventHandlers[$event] as $handler) {
                try {
                    call_user_func($handler, ...$args);
                } catch (\Exception $e) {
                    $this->log('error', "Event handler error for {$event}: {$e->getMessage()}");
                }
            }
        }
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
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->config['logging'] && $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Retourne les statistiques du serveur
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_connections' => count($this->connections),
            'total_channels' => count($this->channels),
            'channels' => array_map(fn($storage) => count($storage), $this->channels),
            'uptime' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
        ];
    }

    /**
     * Retourne l'adresse IP du serveur
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->config['host'];
    }

    /**
     * Retourne le port du serveur
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->config['port'];
    }

    /**
     * Vérifie si le serveur est en cours d'exécution
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Retourne l'intervalle de heartbeat
     *
     * @return int
     */
    public function getHeartbeatInterval(): int
    {
        return $this->config['heartbeat_interval'];
    }

    /**
     * Retourne le nombre maximum de connexions
     *
     * @return int
     */
    public function getMaxConnections(): int
    {
        return $this->config['max_connections'];
    }

    /**
     * Démarre le serveur en mode mock pour les tests
     *
     * @return bool
     */
    public function mockStart(): bool
    {
        $this->running = true;
        return true;
    }

    /**
     * Arrête le serveur
     *
     * @return void
     */
    public function stop(): void
    {
        $this->running = false;
        $this->log('info', 'WebSocket server stopped');
    }
}