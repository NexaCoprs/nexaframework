<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;

/**
 * Gestionnaire des messages WebSocket
 * 
 * Cette classe gère les messages reçus des clients WebSocket.
 */
class MessageHandler
{
    /**
     * Gère un message reçu d'un client WebSocket
     *
     * @param ConnectionInterface $from La connexion qui a envoyé le message
     * @param string $msg Le message reçu
     * @return void
     */
    public function handle(ConnectionInterface $from, string $msg): void
    {
        // Log du message reçu
        echo "Message reçu de ({$from->resourceId}): {$msg}\n";
        
        try {
            // Décoder le message JSON
            $data = json_decode($msg, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->sendError($from, 'Format de message invalide');
                return;
            }
            
            // Traiter le message selon son type
            $this->processMessage($from, $data);
            
        } catch (\Exception $e) {
            $this->sendError($from, 'Erreur lors du traitement du message: ' . $e->getMessage());
        }
    }
    
    /**
     * Traite un message selon son type
     *
     * @param ConnectionInterface $from La connexion source
     * @param array $data Les données du message
     * @return void
     */
    protected function processMessage(ConnectionInterface $from, array $data): void
    {
        $type = $data['type'] ?? 'unknown';
        
        switch ($type) {
            case 'ping':
                $this->handlePing($from, $data);
                break;
                
            case 'chat':
                $this->handleChatMessage($from, $data);
                break;
                
            case 'join_channel':
                $this->handleJoinChannel($from, $data);
                break;
                
            case 'leave_channel':
                $this->handleLeaveChannel($from, $data);
                break;
                
            default:
                $this->sendError($from, "Type de message non supporté: {$type}");
        }
    }
    
    /**
     * Gère un ping
     *
     * @param ConnectionInterface $from La connexion source
     * @param array $data Les données du message
     * @return void
     */
    protected function handlePing(ConnectionInterface $from, array $data): void
    {
        $from->send(json_encode([
            'type' => 'pong',
            'timestamp' => time()
        ]));
    }
    
    /**
     * Gère un message de chat
     *
     * @param ConnectionInterface $from La connexion source
     * @param array $data Les données du message
     * @return void
     */
    protected function handleChatMessage(ConnectionInterface $from, array $data): void
    {
        // Implémentez votre logique de chat ici
        $message = $data['message'] ?? '';
        $channel = $data['channel'] ?? 'general';
        
        // Exemple : diffuser le message aux autres clients du canal
        // $this->broadcastToChannel($channel, [
        //     'type' => 'chat_message',
        //     'message' => $message,
        //     'from' => $from->resourceId,
        //     'timestamp' => time()
        // ], $from);
    }
    
    /**
     * Gère l'adhésion à un canal
     *
     * @param ConnectionInterface $from La connexion source
     * @param array $data Les données du message
     * @return void
     */
    protected function handleJoinChannel(ConnectionInterface $from, array $data): void
    {
        $channel = $data['channel'] ?? null;
        
        if (!$channel) {
            $this->sendError($from, 'Nom du canal requis');
            return;
        }
        
        // Implémentez votre logique d'adhésion au canal ici
        $from->send(json_encode([
            'type' => 'channel_joined',
            'channel' => $channel
        ]));
    }
    
    /**
     * Gère la sortie d'un canal
     *
     * @param ConnectionInterface $from La connexion source
     * @param array $data Les données du message
     * @return void
     */
    protected function handleLeaveChannel(ConnectionInterface $from, array $data): void
    {
        $channel = $data['channel'] ?? null;
        
        if (!$channel) {
            $this->sendError($from, 'Nom du canal requis');
            return;
        }
        
        // Implémentez votre logique de sortie du canal ici
        $from->send(json_encode([
            'type' => 'channel_left',
            'channel' => $channel
        ]));
    }
    
    /**
     * Envoie un message d'erreur à une connexion
     *
     * @param ConnectionInterface $conn La connexion
     * @param string $message Le message d'erreur
     * @return void
     */
    protected function sendError(ConnectionInterface $conn, string $message): void
    {
        $conn->send(json_encode([
            'type' => 'error',
            'message' => $message,
            'timestamp' => time()
        ]));
    }
}