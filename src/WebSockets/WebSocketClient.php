<?php

namespace Nexa\WebSockets;

/**
 * Client WebSocket pour le framework Nexa
 * 
 * Cette classe facilite la communication avec le serveur WebSocket depuis PHP.
 * 
 * @package Nexa\WebSockets
 */
class WebSocketClient
{
    /**
     * URL du serveur WebSocket
     *
     * @var string
     */
    protected $url;

    /**
     * Connexion WebSocket
     *
     * @var resource|null
     */
    protected $connection;

    /**
     * Configuration du client
     *
     * @var array
     */
    protected $config;

    /**
     * Token d'authentification
     *
     * @var string|null
     */
    protected $authToken;

    /**
     * Gestionnaires d'événements
     *
     * @var array
     */
    protected $eventHandlers = [];

    /**
     * État de la connexion
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Constructeur
     *
     * @param string $url URL du serveur WebSocket
     * @param array $config Configuration du client
     */
    public function __construct(string $url, array $config = [])
    {
        $this->url = $url;
        $this->config = array_merge([
            'timeout' => 30,
            'headers' => [],
            'origin' => null,
            'protocol' => null,
            'fragment_size' => 4096,
            'auto_reconnect' => true,
            'reconnect_delay' => 5
        ], $config);
    }

    /**
     * Se connecte au serveur WebSocket
     *
     * @return bool
     * @throws \Exception
     */
    public function connect(): bool
    {
        if ($this->connected) {
            return true;
        }

        $headers = $this->buildHeaders();
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config['timeout']
            ]
        ]);

        $this->connection = stream_socket_client(
            $this->url,
            $errno,
            $errstr,
            $this->config['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->connection) {
            throw new \Exception("Failed to connect to WebSocket server: {$errstr} ({$errno})");
        }

        // Envoyer la requête de handshake WebSocket
        $handshake = $this->buildHandshake();
        fwrite($this->connection, $handshake);

        // Lire la réponse du handshake
        $response = fread($this->connection, 1024);
        if (!$this->validateHandshakeResponse($response)) {
            fclose($this->connection);
            throw new \Exception('WebSocket handshake failed');
        }

        $this->connected = true;
        $this->fireEvent('connected');

        // Authentification automatique si un token est défini
        if ($this->authToken) {
            $this->authenticate($this->authToken);
        }

        return true;
    }

    /**
     * Se déconnecte du serveur WebSocket
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            // Envoyer une trame de fermeture
            $this->sendFrame(chr(0x88) . chr(0x00));
            fclose($this->connection);
            $this->connection = null;
        }

        $this->connected = false;
        $this->fireEvent('disconnected');
    }

    /**
     * Envoie un message au serveur
     *
     * @param array $message
     * @return bool
     */
    public function send(array $message): bool
    {
        if (!$this->connected) {
            throw new \Exception('Not connected to WebSocket server');
        }

        $payload = json_encode($message);
        return $this->sendFrame($this->encodeFrame($payload));
    }

    /**
     * S'authentifie auprès du serveur
     *
     * @param string $token
     * @return bool
     */
    public function authenticate(string $token): bool
    {
        $this->authToken = $token;
        
        return $this->send([
            'type' => 'auth',
            'data' => ['token' => $token]
        ]);
    }

    /**
     * Rejoint un canal
     *
     * @param string $channel
     * @return bool
     */
    public function joinChannel(string $channel): bool
    {
        return $this->send([
            'type' => 'join_channel',
            'data' => ['channel' => $channel]
        ]);
    }

    /**
     * Quitte un canal
     *
     * @param string $channel
     * @return bool
     */
    public function leaveChannel(string $channel): bool
    {
        return $this->send([
            'type' => 'leave_channel',
            'data' => ['channel' => $channel]
        ]);
    }

    /**
     * Envoie un message à un canal
     *
     * @param string $channel
     * @param mixed $message
     * @return bool
     */
    public function sendToChannel(string $channel, $message): bool
    {
        return $this->send([
            'type' => 'message',
            'data' => [
                'channel' => $channel,
                'message' => $message
            ]
        ]);
    }

    /**
     * Envoie un ping au serveur
     *
     * @param array $data
     * @return bool
     */
    public function ping(array $data = []): bool
    {
        return $this->send([
            'type' => 'ping',
            'data' => $data
        ]);
    }

    /**
     * Écoute les messages du serveur
     *
     * @param callable|null $callback
     * @return void
     */
    public function listen(callable $callback = null): void
    {
        if (!$this->connected) {
            throw new \Exception('Not connected to WebSocket server');
        }

        while ($this->connected) {
            $frame = $this->readFrame();
            
            if ($frame === false) {
                // Connexion fermée
                $this->connected = false;
                $this->fireEvent('disconnected');
                break;
            }

            if ($frame !== null) {
                $message = json_decode($frame, true);
                
                if ($message) {
                    $this->handleMessage($message);
                    
                    if ($callback) {
                        call_user_func($callback, $message);
                    }
                }
            }
        }
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
     * Vérifie si le client est connecté
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Construit les en-têtes HTTP pour la connexion
     *
     * @return array
     */
    protected function buildHeaders(): array
    {
        $headers = array_merge([
            'User-Agent' => 'Nexa WebSocket Client/1.0',
            'Connection' => 'Upgrade',
            'Upgrade' => 'websocket',
            'Sec-WebSocket-Version' => '13'
        ], $this->config['headers']);

        if ($this->config['origin']) {
            $headers['Origin'] = $this->config['origin'];
        }

        if ($this->config['protocol']) {
            $headers['Sec-WebSocket-Protocol'] = $this->config['protocol'];
        }

        return $headers;
    }

    /**
     * Construit la requête de handshake WebSocket
     *
     * @return string
     */
    protected function buildHandshake(): string
    {
        $key = base64_encode(random_bytes(16));
        $headers = $this->buildHeaders();
        $headers['Sec-WebSocket-Key'] = $key;

        $parsedUrl = parse_url($this->url);
        $path = ($parsedUrl['path'] ?? '/') . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '');
        $host = $parsedUrl['host'] . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');

        $request = "GET {$path} HTTP/1.1\r\n";
        $request .= "Host: {$host}\r\n";
        
        foreach ($headers as $name => $value) {
            $request .= "{$name}: {$value}\r\n";
        }
        
        $request .= "\r\n";

        return $request;
    }

    /**
     * Valide la réponse du handshake
     *
     * @param string $response
     * @return bool
     */
    protected function validateHandshakeResponse(string $response): bool
    {
        return strpos($response, 'HTTP/1.1 101') === 0 &&
               strpos($response, 'Upgrade: websocket') !== false &&
               strpos($response, 'Connection: Upgrade') !== false;
    }

    /**
     * Encode une trame WebSocket
     *
     * @param string $payload
     * @return string
     */
    protected function encodeFrame(string $payload): string
    {
        $length = strlen($payload);
        $frame = chr(0x81); // FIN + opcode text

        if ($length < 126) {
            $frame .= chr($length | 0x80); // Masqué
        } elseif ($length < 65536) {
            $frame .= chr(126 | 0x80) . pack('n', $length);
        } else {
            $frame .= chr(127 | 0x80) . pack('J', $length);
        }

        // Masque aléatoire
        $mask = random_bytes(4);
        $frame .= $mask;

        // Appliquer le masque au payload
        for ($i = 0; $i < $length; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        return $frame;
    }

    /**
     * Lit une trame WebSocket
     *
     * @return string|null|false
     */
    protected function readFrame()
    {
        if (!$this->connection) {
            return false;
        }

        $header = fread($this->connection, 2);
        if (strlen($header) < 2) {
            return false;
        }

        $firstByte = ord($header[0]);
        $secondByte = ord($header[1]);

        $opcode = $firstByte & 0x0F;
        $masked = ($secondByte & 0x80) === 0x80;
        $length = $secondByte & 0x7F;

        if ($length === 126) {
            $length = unpack('n', fread($this->connection, 2))[1];
        } elseif ($length === 127) {
            $length = unpack('J', fread($this->connection, 8))[1];
        }

        $mask = null;
        if ($masked) {
            $mask = fread($this->connection, 4);
        }

        $payload = fread($this->connection, $length);

        if ($masked && $mask) {
            for ($i = 0; $i < $length; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        }

        // Gérer les différents opcodes
        switch ($opcode) {
            case 0x8: // Close
                return false;
            case 0x9: // Ping
                $this->sendFrame(chr(0x8A) . chr(0x00)); // Pong
                return null;
            case 0xA: // Pong
                return null;
            case 0x1: // Text
                return $payload;
            default:
                return null;
        }
    }

    /**
     * Envoie une trame brute
     *
     * @param string $frame
     * @return bool
     */
    protected function sendFrame(string $frame): bool
    {
        if (!$this->connection) {
            return false;
        }

        $written = fwrite($this->connection, $frame);
        return $written === strlen($frame);
    }

    /**
     * Gère un message reçu
     *
     * @param array $message
     * @return void
     */
    protected function handleMessage(array $message): void
    {
        $type = $message['type'] ?? null;
        
        switch ($type) {
            case 'auth.success':
                $this->fireEvent('authenticated', $message['data'] ?? []);
                break;
                
            case 'channel.joined':
                $this->fireEvent('channel.joined', $message['data'] ?? []);
                break;
                
            case 'channel.left':
                $this->fireEvent('channel.left', $message['data'] ?? []);
                break;
                
            case 'channel.message':
                $this->fireEvent('channel.message', $message['data'] ?? []);
                break;
                
            case 'error':
                $this->fireEvent('error', $message['data'] ?? []);
                break;
                
            case 'pong':
                $this->fireEvent('pong', $message['data'] ?? []);
                break;
                
            default:
                $this->fireEvent('message', $message);
                break;
        }
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
                    // Ignorer les erreurs des gestionnaires d'événements
                }
            }
        }
    }

    /**
     * Destructeur
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}