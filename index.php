<?php
/**
 * Nexa Framework Bootstrap
 * Point d'entrée principal de l'application
 */

// Définir les chemins de base
define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Charger l'autoloader de Composer
require_once BASE_PATH . '/vendor/autoload.php';

// Charger les helpers
require_once BASE_PATH . '/src/Nexa/Core/helpers.php';

// Initialiser l'application
use Nexa\Core\Application;
use Nexa\Routing\Router;
use Nexa\Http\Request;
use Nexa\Http\Response;

try {
    // Créer l'instance de l'application
    $app = new Application(BASE_PATH);
    
    // Créer la requête
    $request = Request::capture();
    $method = $request->method();
    $uri = $request->uri();
    
    // Nettoyer l'URI
    $uri = parse_url($uri, PHP_URL_PATH);
    $uri = rtrim($uri, '/');
    if (empty($uri)) {
        $uri = '/';
    }
    
    // Utiliser l'URI tel quel pour le dispatch
    $dispatchUri = $uri;
    
    // Charger les routes web
    $router = require BASE_PATH . '/routes/web.php';
    
    if (!$router instanceof Router) {
        throw new Exception('Web routes file must return a Router instance');
    }
    

    
    // Charger les routes API et les fusionner
    $apiRouter = require BASE_PATH . '/routes/api.php';
    if ($apiRouter instanceof Router) {
        $router->mergeRouters($apiRouter);
    }
    

    
    // Traiter la requête
    $response = $router->dispatch($request->method(), $dispatchUri);
    
    // Envoyer la réponse
    if ($response instanceof Response) {
        $response->send();
    } else {
        // Si ce n'est pas une Response, on l'affiche directement
        echo $response;
    }
    
} catch (Exception $e) {
    // Gestion des erreurs
    $isApiRequest = strpos($uri ?? '', '/api') === 0;
    
    if ($isApiRequest) {
        // Réponse JSON pour les erreurs API
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Server Error',
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ], JSON_PRETTY_PRINT);
    } else {
        // Réponse HTML pour les erreurs web
        http_response_code(500);
        echo "<!DOCTYPE html><html><head><title>Error 500</title></head><body>";
        echo "<h1>500 - Internal Server Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
        echo "</body></html>";
    }
}

/**
 * Fonction pour fusionner deux routeurs
 */
function mergeRouters(Router $mainRouter, Router $secondaryRouter) {
    // Utiliser la réflexion pour accéder aux routes protégées
    $reflection = new ReflectionClass($secondaryRouter);
    $routesProperty = $reflection->getProperty('routes');
    $routesProperty->setAccessible(true);
    $secondaryRoutes = $routesProperty->getValue($secondaryRouter);
    
    $mainReflection = new ReflectionClass($mainRouter);
    $mainRoutesProperty = $mainReflection->getProperty('routes');
    $mainRoutesProperty->setAccessible(true);
    $mainRoutes = $mainRoutesProperty->getValue($mainRouter);
    
    // Debug: vérifier les routes avant fusion
    error_log("Main router routes count: " . count($mainRoutes));
    error_log("Secondary router routes count: " . count($secondaryRoutes));
    
    // Debug: afficher les routes principales
    foreach ($mainRoutes as $method => $routes) {
        error_log("Main $method routes: " . count($routes));
        foreach ($routes as $route) {
            $routeReflection = new ReflectionClass($route);
            $uriProperty = $routeReflection->getProperty('uri');
            $uriProperty->setAccessible(true);
            error_log("  Main route: " . $uriProperty->getValue($route));
        }
    }
    
    // Debug: afficher les routes secondaires
    foreach ($secondaryRoutes as $method => $routes) {
        error_log("Secondary $method routes: " . count($routes));
        foreach ($routes as $route) {
            $routeReflection = new ReflectionClass($route);
            $uriProperty = $routeReflection->getProperty('uri');
            $uriProperty->setAccessible(true);
            error_log("  Secondary route: " . $uriProperty->getValue($route));
        }
    }
    
    // Fusionner les routes en préservant les routes existantes
    foreach ($secondaryRoutes as $method => $methodRoutes) {
        if (!isset($mainRoutes[$method])) {
            $mainRoutes[$method] = [];
        }
        // Utiliser array_merge pour préserver toutes les routes
        $mainRoutes[$method] = array_merge($mainRoutes[$method], $methodRoutes);
    }
    
    // Debug: vérifier les routes après fusion
    error_log("Merged router routes count: " . count($mainRoutes));
    foreach ($mainRoutes as $method => $routes) {
        error_log("Merged $method routes: " . count($routes));
        foreach ($routes as $route) {
            $routeReflection = new ReflectionClass($route);
            $uriProperty = $routeReflection->getProperty('uri');
            $uriProperty->setAccessible(true);
            error_log("  Merged route: " . $uriProperty->getValue($route));
        }
    }
    
    $mainRoutesProperty->setValue($mainRouter, $mainRoutes);
    
    return $mainRouter;
}