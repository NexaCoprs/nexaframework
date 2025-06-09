<?php

namespace Nexa\View;

class TemplateEngine
{
    protected $viewsPath;
    protected $cachePath;
    protected $tailwindEnabled = true;

    public function __construct($viewsPath, $cachePath)
    {
        $this->viewsPath = rtrim($viewsPath, '\\/');
        $this->cachePath = rtrim($cachePath, '\\/');
        
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function enableTailwind($enabled = true)
    {
        $this->tailwindEnabled = $enabled;
        return $this;
    }

    public function render($template, $data = [])
    {
        $templateFile = $this->viewsPath . '/' . $template . '.nx';

        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template '{$template}' not found at {$templateFile}");
        }

        $compiled = $this->compile(file_get_contents($templateFile), $data);

        if ($this->tailwindEnabled) {
            $compiled = $this->injectTailwind($compiled);
        }

        // Optionnel : write to cache file
        $cachedFile = $this->cachePath . '/' . md5($templateFile) . '.php';
        file_put_contents($cachedFile, $compiled);

        // Exécuter le template compilé (sandbox style)
        extract($data);
        ob_start();
        eval(' ?>' . $compiled . '<?php ');
        return ob_get_clean();
    }

    protected function compile($content, $data)
    {
        // Système de templating simple : {{ variable }}
        return preg_replace_callback('/{{\s*(.+?)\s*}}/', function ($matches) use ($data) {
            $key = trim($matches[1]);
            return isset($data[$key]) ? htmlspecialchars($data[$key], ENT_QUOTES, 'UTF-8') : '';
        }, $content);
    }

    protected function injectTailwind($content)
    {
        // Inject Tailwind CSS CDN si nécessaire
        $tailwindCDN = '<script src="https://cdn.tailwindcss.com"></script>';
        if (stripos($content, '</head>') !== false) {
            return str_ireplace('</head>', $tailwindCDN . "\n</head>", $content);
        }
        return $tailwindCDN . "\n" . $content;
    }
}
