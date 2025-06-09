<?php

namespace Nexa\Http\Middleware;

use Nexa\Http\Request;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Gère une requête entrante
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Vérifier si l'utilisateur est authentifié
        if (!$this->isAuthenticated($request)) {
            // Rediriger vers la page de connexion ou retourner une erreur
            if ($request->expectsJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized']);
                exit;
            } else {
                header('Location: /login');
                exit;
            }
        }

        return $next($request);
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     *
     * @param Request $request
     * @return bool
     */
    protected function isAuthenticated(Request $request)
    {
        // Vérifier la session
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Obtient l'utilisateur authentifié
     *
     * @return array|null
     */
    public static function user()
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'] ?? null,
                'email' => $_SESSION['user_email'] ?? null
            ];
        }

        return null;
    }

    /**
     * Connecte un utilisateur
     *
     * @param array $user
     * @return void
     */
    public static function login($user)
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'] ?? null;
        $_SESSION['user_email'] = $user['email'] ?? null;
        
        // Régénérer l'ID de session pour la sécurité seulement si la session est active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * Déconnecte l'utilisateur
     *
     * @return void
     */
    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Supprimer toutes les variables de session
        $_SESSION = [];

        // Détruire le cookie de session seulement si les headers n'ont pas été envoyés
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        // Détruire la session seulement si elle est active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     *
     * @param string $role
     * @return bool
     */
    public static function hasRole(string $role)
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userRoles = $_SESSION['user_roles'] ?? [];
        return in_array($role, $userRoles);
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     *
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(string $permission)
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $userPermissions = $_SESSION['user_permissions'] ?? [];
        return in_array($permission, $userPermissions);
    }
}