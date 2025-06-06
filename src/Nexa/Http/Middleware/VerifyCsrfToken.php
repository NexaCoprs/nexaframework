<?php

namespace Nexa\Http\Middleware;

use Closure;
use Nexa\Http\Request;

class VerifyCsrfToken implements MiddlewareInterface
{
    /**
     * Les URIs qui doivent être exclues de la vérification CSRF
     *
     * @var array
     */
    protected $except = [
        // 'api/*',
    ];

    /**
     * Traite une requête entrante
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if ($this->isReading($request) || $this->tokensMatch($request)) {
            return $next($request);
        }

        throw new \Exception('CSRF token mismatch.', 419);
    }

    /**
     * Vérifie si la requête doit être ignorée
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkip($request)
    {
        foreach ($this->except as $except) {
            if ($this->matchesPattern($request->path(), $except)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si la requête est en lecture seule
     *
     * @param Request $request
     * @return bool
     */
    protected function isReading($request)
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Vérifie si les tokens CSRF correspondent
     *
     * @param Request $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $this->getTokenFromRequest($request);
        $sessionToken = $this->getTokenFromSession();

        return is_string($sessionToken) && is_string($token) && hash_equals($sessionToken, $token);
    }

    /**
     * Obtient le token CSRF depuis la requête
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest($request)
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
            $token = $this->decryptCookieToken($header);
        }

        return $token;
    }

    /**
     * Obtient le token CSRF depuis la session
     *
     * @return string|null
     */
    protected function getTokenFromSession()
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = $this->generateToken();
        }

        return $_SESSION['_token'];
    }

    /**
     * Génère un nouveau token CSRF
     *
     * @return string
     */
    protected function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Décrypte le token depuis un cookie
     *
     * @param string $token
     * @return string
     */
    protected function decryptCookieToken($token)
    {
        // Pour l'instant, on retourne le token tel quel
        // Dans une implémentation complète, on décrypterait le token
        return $token;
    }

    /**
     * Vérifie si un chemin correspond à un pattern
     *
     * @param string $path
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern($path, $pattern)
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        
        return preg_match('#^' . $pattern . '$#', $path) === 1;
    }

    /**
     * Obtient le token CSRF actuel
     *
     * @return string
     */
    public static function token()
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}