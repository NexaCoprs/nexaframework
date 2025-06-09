<?php

namespace Nexa\Core;

abstract class ServiceProvider
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    abstract public function register();

    public function boot()
    {
        // Default implementation - can be overridden
    }
}