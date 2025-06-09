<?php

namespace App\Http\Controllers\Api;

use Nexa\Http\Controller;
use Nexa\Http\Request;
use Nexa\Http\Response;
use Nexa\Auth\JWTManager;
use Nexa\Core\Logger;
use App\Models\User;

class ApiController extends Controller
{
    protected $jwtManager;

    public function __construct()
    {
        $this->jwtManager = new JWTManager();
    }

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');

            if (!$email || !$password) {
                return $this->jsonResponse([
                    'error' => 'Email et mot de passe requis'
                ], 400);
            }

            // Ici vous devriez vérifier les credentials en base
            // Pour l'exemple, on simule une authentification
            if ($email === 'admin@example.com' && $password === 'password') {
                $token = $this->jwtManager->generateToken(1, $email);

                return $this->jsonResponse([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => 1,
                        'email' => $email
                    ]
                ]);
            }

            return $this->jsonResponse([
                'error' => 'Identifiants invalides'
            ], 401);

        } catch (\Exception $e) {
            Logger::error('Erreur lors de la connexion: ' . $e->getMessage());
            return $this->jsonResponse([
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Inscription utilisateur
     */
    public function register(Request $request)
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');
            $name = $request->input('name');

            if (!$email || !$password || !$name) {
                return $this->jsonResponse([
                    'error' => 'Nom, email et mot de passe requis'
                ], 400);
            }

            // Ici vous devriez créer l'utilisateur en base
            // Pour l'exemple, on simule la création
            $userId = rand(1000, 9999);

            $token = $this->jwtManager->generateToken($userId, $email);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email
                ]
            ], 201);

        } catch (\Exception $e) {
            Logger::error('Erreur lors de l\'inscription: ' . $e->getMessage());
            return $this->jsonResponse([
                'error' => 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    /**
     * Obtenir l'utilisateur actuel
     */
    public function user(Request $request)
    {
        // Ici vous devriez récupérer l'utilisateur depuis le token JWT
        return $this->jsonResponse([
            'user' => [
                'id' => 1,
                'name' => 'Utilisateur Test',
                'email' => 'admin@example.com'
            ]
        ]);
    }

    /**
     * Liste des utilisateurs
     */
    public function users(Request $request)
    {
        return $this->jsonResponse([
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
            ]
        ]);
    }

    /**
     * Créer un utilisateur
     */
    public function createUser(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');

        if (!$name || !$email) {
            return $this->jsonResponse([
                'error' => 'Nom et email requis'
            ], 400);
        }

        return $this->jsonResponse([
            'success' => true,
            'message' => 'Utilisateur créé',
            'user' => [
                'id' => rand(1000, 9999),
                'name' => $name,
                'email' => $email
            ]
        ], 201);
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(Request $request, $id)
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => 'Utilisateur mis à jour',
            'user' => [
                'id' => $id,
                'name' => $request->input('name', 'Nom mis à jour'),
                'email' => $request->input('email', 'email@example.com')
            ]
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(Request $request, $id)
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => 'Utilisateur supprimé'
        ]);
    }

    /**
     * Status de l'API
     */
    public function status(Request $request)
    {
        return $this->jsonResponse([
            'status' => 'OK',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Health check
     */
    public function health(Request $request)
    {
        return $this->jsonResponse([
            'health' => 'OK',
            'services' => [
                'database' => 'OK',
                'cache' => 'OK',
                'storage' => 'OK'
            ]
        ]);
    }

    /**
     * Retourne une réponse JSON
     */
    protected function jsonResponse($data, $status = 200)
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
        return $response;
    }
}