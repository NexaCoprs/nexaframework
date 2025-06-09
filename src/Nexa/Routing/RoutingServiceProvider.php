<?php

namespace Nexa\Routing;

use Nexa\Core\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('router', function () {
            return new Router();
        });
    }

    public function boot()
    {
        // Load web routes
        $webRoutes = $this->app->basePath('routes/web.php');
        if (file_exists($webRoutes)) {
            $router = require $webRoutes;
            // Replace the singleton with the router that has routes defined
            $this->app->singleton('router', function () use ($router) {
                return $router;
            });
        }
    }
}