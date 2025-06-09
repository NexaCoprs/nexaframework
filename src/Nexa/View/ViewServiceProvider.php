<?php

namespace Nexa\View;

use Nexa\Core\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('view', function () {
            $viewsPath = $this->app->basePath('resources/views');
            $cachePath = $this->app->basePath('storage/cache/views');
            
            return new TemplateEngine($viewsPath, $cachePath);
        });
    }

    public function boot()
    {
        // Boot logic if needed
    }
}