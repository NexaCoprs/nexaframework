<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Gestionnaire des connexions WebSocket
 * 
 * Cette classe gère les événements de connexion des clients WebSocket.
 */
class ConnectionHandler
{
    /**
     * Gère une nouvelle connexion WebSocket
     *
     * @param ConnectionInterface $conn La connexion WebSocket
     * @return void
     */
    public function handle(ConnectionInterface $conn): void
    {
        // Log de la nouvelle connexion
        echo "Nouvelle connexion ({$conn->resourceId})\n";
        
        // Vous pouvez ajouter ici votre logique personnalisée :
        // - Authentification
        // - Stockage de la connexion
        // - Notification aux autres clients
        // - etc.
        
        // Exemple : envoyer un message de bienvenue
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => 'Connexion établie avec succès',
            'connection_id' => $conn->resourceId
        ]));
    }
    
    /**
     * Vérifie si une connexion est autorisée
     *
     * @param ConnectionInterface $conn La connexion WebSocket
     * @return bool
     */
    public function isAuthorized(ConnectionInterface $conn): bool
    {
        // Implémentez votre logique d'autorisation ici
        // Par exemple, vérifier un token dans les headers
        return true;
    }
}