<?php

use Nexa\Routing\Router;
use App\Http\Controllers\Api\ApiController;

$apiRouter = new Router();

// Routes API avec préfixe /api
$apiRouter->group(['prefix' => 'api'], function($router) {
    // Routes d'authentification
    $router->post('/auth/login', [ApiController::class, 'login']);
    $router->post('/auth/register', [ApiController::class, 'register']);
    $router->post('/auth/logout', [ApiController::class, 'logout']);
    
    // Routes protégées
    $router->group(['middleware' => 'auth'], function($router) {
        $router->get('/user', [ApiController::class, 'user']);
        $router->get('/users', [ApiController::class, 'users']);
        $router->post('/users', [ApiController::class, 'createUser']);
        $router->put('/users/{id}', [ApiController::class, 'updateUser']);
        $router->delete('/users/{id}', [ApiController::class, 'deleteUser']);
    });
    
    // Routes publiques
    $router->get('/status', [ApiController::class, 'status']);
    $router->get('/health', [ApiController::class, 'health']);
});

return $apiRouter;