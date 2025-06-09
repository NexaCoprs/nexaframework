<?php

namespace Nexa\Http\Middleware;

use Closure;

interface MiddlewareInterface
{
    /**
     * Traite une requête entrante
     *
     * @param mixed $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next);
}