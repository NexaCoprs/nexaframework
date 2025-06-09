<?php
/**
 * Nexa Framework Bootstrap
 * Point d'entrée principal de l'application
 */

// Définir les chemins de base
define('BASE_PATH', __DIR__);
<<<<<<< HEAD
define('WORKSPACE_PATH', BASE_PATH . '/workspace');
define('CONFIG_PATH', WORKSPACE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('ASSETS_PATH', WORKSPACE_PATH . '/assets');
define('HANDLERS_PATH', WORKSPACE_PATH . '/handlers');
define('DATABASE_PATH', WORKSPACE_PATH . '/database');
define('INTERFACE_PATH', WORKSPACE_PATH . '/interface');
define('FLOWS_PATH', WORKSPACE_PATH . '/flows');
=======
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77

// Charger l'autoloader de Composer
require_once BASE_PATH . '/vendor/autoload.php';

<<<<<<< HEAD
// Charger les variables d'environnement
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Ignorer les commentaires
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Supprimer les guillemets si présents
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            }
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}

// Charger les helpers
require_once BASE_PATH . '/kernel/Nexa/Core/helpers.php';

// Charger manuellement les plugins
require_once BASE_PATH . '/kernel/Plugins/PluginManager.php';
require_once BASE_PATH . '/kernel/Plugins/Plugin.php';
=======
// Charger les helpers
require_once BASE_PATH . '/src/Nexa/Core/helpers.php';
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77

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
<<<<<<< HEAD
    $router = require FLOWS_PATH . '/web.php';
=======
    $router = require BASE_PATH . '/routes/web.php';
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77
    
    if (!$router instanceof Router) {
        throw new Exception('Web routes file must return a Router instance');
    }
    
<<<<<<< HEAD
    // Charger les routes API et les fusionner
    $apiRouter = require FLOWS_PATH . '/api.php';
=======

    
    // Charger les routes API et les fusionner
    $apiRouter = require BASE_PATH . '/routes/api.php';
>>>>>>> a19def95d90bc4dc9e0cca1d7df7f97a4761be77
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