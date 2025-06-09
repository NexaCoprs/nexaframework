<?php

namespace Nexa\Http;

use Nexa\View\TemplateEngine;

class Controller
{
    protected $templateEngine;

    public function __construct()
    {
        $this->templateEngine = new TemplateEngine(
            $this->resourcesPath('views'),
            $this->storagePath('framework/views')
        );
    }

    protected function resourcesPath($path = '')
    {
        return dirname(__DIR__, 3) . '/resources/' . ltrim($path, '/');
    }

    protected function storagePath($path = '')
    {
        return dirname(__DIR__, 3) . '/storage/' . ltrim($path, '/');
    }

    protected function view($template, $data = [])
    {
        return $this->templateEngine->render($template, $data);
    }

    protected function json($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        return json_encode($data);
    }

    protected function redirect($url, $status = 302)
    {
        header('Location: '.$url, true, $status);
        exit;
    }
}