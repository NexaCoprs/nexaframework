<?php

namespace Nexa\Http;

class Request
{
    protected $query;
    protected $request;
    protected $server;
    protected $files;
    protected $cookies;
    protected $headers;
    protected $content;
    protected $method;
    protected $uri;

    public function __construct()
    {
        $this->query = $_GET;
        $this->request = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getHeaders();
        $this->method = $this->server['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->server['REQUEST_URI'] ?? '/';
        $this->content = file_get_contents('php://input');
    }

    /**
     * Crée une nouvelle instance de Request
     *
     * @return static
     */
    public static function capture()
    {
        return new static();
    }

    /**
     * Obtient une valeur depuis les données de la requête
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        $input = array_merge($this->query, $this->request);
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }

    /**
     * Obtient une valeur depuis les paramètres GET
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }

    /**
     * Obtient une valeur depuis les paramètres POST
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }
        
        return $this->request[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe dans les données de la requête
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $input = array_merge($this->query, $this->request);
        return array_key_exists($key, $input);
    }

    /**
     * Vérifie si une clé existe et n'est pas vide
     *
     * @param string $key
     * @return bool
     */
    public function filled($key)
    {
        return $this->has($key) && !empty($this->input($key));
    }

    /**
     * Obtient toutes les données de la requête
     *
     * @return array
     */
    public function all()
    {
        return array_merge($this->query, $this->request);
    }

    /**
     * Obtient seulement les clés spécifiées
     *
     * @param array $keys
     * @return array
     */
    public function only(array $keys)
    {
        $input = $this->all();
        $result = [];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $result[$key] = $input[$key];
            }
        }
        
        return $result;
    }

    /**
     * Obtient toutes les données sauf les clés spécifiées
     *
     * @param array $keys
     * @return array
     */
    public function except(array $keys)
    {
        $input = $this->all();
        
        foreach ($keys as $key) {
            unset($input[$key]);
        }
        
        return $input;
    }

    /**
     * Obtient la méthode HTTP
     *
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Vérifie si la méthode correspond
     *
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Vérifie si la requête est POST
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Vérifie si la requête est GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Vérifie si la requête est PUT
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->isMethod('PUT');
    }

    /**
     * Vérifie si la requête est DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Obtient l'URI de la requête
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Obtient le chemin de l'URI
     *
     * @return string
     */
    public function path()
    {
        return parse_url($this->uri, PHP_URL_PATH) ?: '/';
    }

    /**
     * Obtient un header
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function header($key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Vérifie si la requête est AJAX
     *
     * @return bool
     */
    public function ajax()
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    /**
     * Vérifie si la requête attend du JSON
     *
     * @return bool
     */
    public function wantsJson()
    {
        $acceptable = $this->header('accept', '');
        return strpos($acceptable, 'application/json') !== false;
    }

    /**
     * Obtient le contenu brut de la requête
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Obtient les données JSON décodées
     *
     * @param bool $assoc
     * @return mixed
     */
    public function json($assoc = true)
    {
        return json_decode($this->content, $assoc);
    }

    /**
     * Obtient un fichier uploadé
     *
     * @param string $key
     * @return array|null
     */
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Vérifie si un fichier a été uploadé
     *
     * @param string $key
     * @return bool
     */
    public function hasFile($key)
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtient l'adresse IP du client
     *
     * @return string
     */
    public function ip()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($this->server[$key])) {
                $ip = trim(explode(',', $this->server[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Obtient l'User-Agent
     *
     * @return string
     */
    public function userAgent()
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Vérifie si la requête attend une réponse JSON
     *
     * @return bool
     */
    public function expectsJson()
    {
        return $this->ajax() || $this->wantsJson();
    }

    /**
     * Extrait les headers HTTP
     *
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [];
        
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
}