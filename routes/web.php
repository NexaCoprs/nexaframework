<?php

use Nexa\Routing\Router;
use App\Http\Controllers\WelcomeController;

$webRouter = new Router();

$webRouter->get('/', [WelcomeController::class, 'index']);
$webRouter->get('/about', [WelcomeController::class, 'about']);
$webRouter->get('/documentation', [WelcomeController::class, 'documentation']);
$webRouter->get('/contact', [WelcomeController::class, 'contact']);
$webRouter->post('/contact', [WelcomeController::class, 'contact']);

return $webRouter;