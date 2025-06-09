<?php

namespace App\WebSocket\Handlers;

use Ratchet\ConnectionInterface;
use Exception;

/**
 * Gestionnaire des erreurs WebSocket
 * 
 * Cette classe gère les erreurs qui surviennent dans les connexions WebSocket.
 */
class ErrorHandler
{
    /**
     * Gère une erreur survenue sur une connexion WebSocket
     *
     * @param ConnectionInterface $conn La connexion où l'erreur s'est produite
     * @param Exception $e L'exception qui s'est produite
     * @return void
     */
    public function handle(ConnectionInterface $conn, Exception $e): void
    {
        // Log de l'erreur
        echo "Erreur sur la connexion ({$conn->resourceId}): {$e->getMessage()}\n";
        
        // Log détaillé pour le débogage
        error_log(sprintf(
            "WebSocket Error [Connection: %s]: %s in %s:%d\nStack trace:\n%s",
            $conn->resourceId,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
        
        // Traiter l'erreur selon son type
        $this->processError($conn, $e);
    }
    
    /**
     * Traite une erreur selon son type
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @return void
     */
    protected function processError(ConnectionInterface $conn, Exception $e): void
    {
        // Déterminer le type d'erreur et agir en conséquence
        if ($this->isCriticalError($e)) {
            $this->handleCriticalError($conn, $e);
        } elseif ($this->isAuthenticationError($e)) {
            $this->handleAuthenticationError($conn, $e);
        } elseif ($this->isValidationError($e)) {
            $this->handleValidationError($conn, $e);
        } else {
            $this->handleGenericError($conn, $e);
        }
    }
    
    /**
     * Vérifie si une erreur est critique
     *
     * @param Exception $e L'exception
     * @return bool
     */
    protected function isCriticalError(Exception $e): bool
    {
        // Définir les critères pour les erreurs critiques
        return $e instanceof \Error || 
               $e instanceof \ParseError ||
               strpos($e->getMessage(), 'Fatal') !== false;
    }
    
    /**
     * Vérifie si une erreur est liée à l'authentification
     *
     * @param Exception $e L'exception
     * @return bool
     */
    protected function isAuthenticationError(Exception $e): bool
    {
        return strpos(strtolower($e->getMessage()), 'auth') !== false ||
               strpos(strtolower($e->getMessage()), 'unauthorized') !== false;
    }
    
    /**
     * Vérifie si une erreur est liée à la validation
     *
     * @param Exception $e L'exception
     * @return bool
     */
    protected function isValidationError(Exception $e): bool
    {
        return strpos(strtolower($e->getMessage()), 'validation') !== false ||
               strpos(strtolower($e->getMessage()), 'invalid') !== false;
    }
    
    /**
     * Gère une erreur critique
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @return void
     */
    protected function handleCriticalError(ConnectionInterface $conn, Exception $e): void
    {
        // Pour les erreurs critiques, fermer la connexion
        $this->sendErrorMessage($conn, 'Une erreur critique s\'est produite', 'critical');
        $conn->close();
    }
    
    /**
     * Gère une erreur d'authentification
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @return void
     */
    protected function handleAuthenticationError(ConnectionInterface $conn, Exception $e): void
    {
        $this->sendErrorMessage($conn, 'Erreur d\'authentification', 'auth_error');
        // Optionnellement fermer la connexion
        // $conn->close();
    }
    
    /**
     * Gère une erreur de validation
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @return void
     */
    protected function handleValidationError(ConnectionInterface $conn, Exception $e): void
    {
        $this->sendErrorMessage($conn, 'Données invalides: ' . $e->getMessage(), 'validation_error');
    }
    
    /**
     * Gère une erreur générique
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @return void
     */
    protected function handleGenericError(ConnectionInterface $conn, Exception $e): void
    {
        $this->sendErrorMessage($conn, 'Une erreur s\'est produite', 'generic_error');
    }
    
    /**
     * Envoie un message d'erreur à une connexion
     *
     * @param ConnectionInterface $conn La connexion
     * @param string $message Le message d'erreur
     * @param string $type Le type d'erreur
     * @return void
     */
    protected function sendErrorMessage(ConnectionInterface $conn, string $message, string $type = 'error'): void
    {
        try {
            $conn->send(json_encode([
                'type' => 'error',
                'error_type' => $type,
                'message' => $message,
                'timestamp' => time()
            ]));
        } catch (Exception $sendException) {
            // Si on ne peut pas envoyer le message d'erreur, juste logger
            error_log("Impossible d'envoyer le message d'erreur: " . $sendException->getMessage());
        }
    }
    
    /**
     * Log une erreur dans le système de logs
     *
     * @param ConnectionInterface $conn La connexion
     * @param Exception $e L'exception
     * @param string $context Contexte supplémentaire
     * @return void
     */
    public function logError(ConnectionInterface $conn, Exception $e, string $context = ''): void
    {
        $logMessage = sprintf(
            "[WebSocket Error] Connection: %s, Context: %s, Error: %s, File: %s:%d",
            $conn->resourceId,
            $context,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        error_log($logMessage);
    }
}