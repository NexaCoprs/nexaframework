<?php

namespace Nexa\Http;

class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;

    public function __construct($content = '', $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Définit le contenu de la réponse
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Obtient le contenu de la réponse
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Alias pour getContent()
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getContent();
    }

    /**
     * Définit le code de statut HTTP
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Obtient le code de statut HTTP
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Définit un header
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Obtient un header
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return $this->headers[$name] ?? $default;
    }

    /**
     * Obtient tous les headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Définit plusieurs headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Vérifie si un header existe
     *
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }

    /**
     * Supprime un header
     *
     * @param string $name
     * @return $this
     */
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
        return $this;
    }

    /**
     * Envoie la réponse au client
     *
     * @return void
     */
    public function send()
    {
        // Définir le code de statut HTTP
        http_response_code($this->statusCode);

        // Envoyer les headers
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // Envoyer le contenu
        echo $this->content;
    }

    /**
     * Crée une réponse JSON
     *
     * @param mixed $data
     * @param int $statusCode
     * @param array $headers
     * @return static
     */
    public static function json($data, $statusCode = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data), $statusCode, $headers);
    }

    /**
     * Crée une réponse de redirection
     *
     * @param string $url
     * @param int $statusCode
     * @return static
     */
    public static function redirect($url, $statusCode = 302)
    {
        return new static('', $statusCode, ['Location' => $url]);
    }

    /**
     * Convertit la réponse en chaîne
     *
     * @return string
     */
    public function __toString()
    {
        return $this->content;
    }
}