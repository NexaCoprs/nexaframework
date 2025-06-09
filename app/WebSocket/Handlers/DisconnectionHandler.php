<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;

/**
 * Gestionnaire des déconnexions WebSocket
 * 
 * Cette classe gère les événements de déconnexion des clients WebSocket.
 */
class DisconnectionHandler
{
    /**
     * Gère la déconnexion d'un client WebSocket
     *
     * @param ConnectionInterface $conn La connexion WebSocket qui se déconnecte
     * @return void
     */
    public function handle(ConnectionInterface $conn): void
    {
        // Log de la déconnexion
        echo "Connexion fermée ({$conn->resourceId})\n";
        
        // Vous pouvez ajouter ici votre logique personnalisée :
        // - Nettoyage des données de session
        // - Notification aux autres clients
        // - Mise à jour du statut utilisateur
        // - Sauvegarde des données temporaires
        // - etc.
        
        // Exemple : nettoyer les données de la connexion
        $this->cleanup($conn);
    }
    
    /**
     * Nettoie les données associées à une connexion
     *
     * @param ConnectionInterface $conn La connexion WebSocket
     * @return void
     */
    protected function cleanup(ConnectionInterface $conn): void
    {
        // Implémentez votre logique de nettoyage ici
        // Par exemple :
        // - Supprimer la connexion des canaux
        // - Mettre à jour le statut utilisateur en base
        // - Libérer les ressources
    }
    
    /**
     * Notifie les autres clients de la déconnexion
     *
     * @param ConnectionInterface $conn La connexion WebSocket
     * @param array $channels Les canaux à notifier
     * @return void
     */
    public function notifyDisconnection(ConnectionInterface $conn, array $channels = []): void
    {
        // Implémentez la logique de notification ici
        foreach ($channels as $channel) {
            // Envoyer une notification de déconnexion aux autres clients du canal
        }
    }
}