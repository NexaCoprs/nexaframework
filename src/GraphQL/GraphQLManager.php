<?php

namespace Nexa\GraphQL;

use Nexa\Core\Application;
use Nexa\Http\Request;
use Nexa\Http\Response;

/**
 * Gestionnaire GraphQL pour Nexa Framework
 * 
 * Cette classe est responsable de la gestion des requêtes GraphQL,
 * du schéma et de l'exécution des requêtes.
 * 
 * @package Nexa\GraphQL
 */
class GraphQLManager
{
    /**
     * Instance de l'application Nexa
     *
     * @var \Nexa\Core\Application
     */
    protected $app;

    /**
     * Schémas GraphQL disponibles
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * Types GraphQL disponibles
     *
     * @var array
     */
    protected $types = [];

    /**
     * Requêtes GraphQL disponibles
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Mutations GraphQL disponibles
     *
     * @var array
     */
    protected $mutations = [];

    /**
     * Middleware appliqué aux requêtes GraphQL
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * Constructeur
     *
     * @param \Nexa\Core\Application $app Instance de l'application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->loadConfiguration();
    }

    /**
     * Charge la configuration GraphQL
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $config = $this->app['config']->get('graphql', []);
        
        $this->schemas = $config['schemas'] ?? [];
        $this->types = $config['types'] ?? [];
        
        // Auto-découverte si activée
        if ($config['auto_discover']['enabled'] ?? false) {
            $this->discoverGraphQLClasses($config['auto_discover']['directories'] ?? []);
        }
    }

    /**
     * Découvre automatiquement les classes GraphQL
     *
     * @param array $directories Répertoires à scanner
     * @return void
     */
    protected function discoverGraphQLClasses(array $directories): void
    {
        foreach ($directories as $type => $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = glob($directory . '/*.php');
            
            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);
                
                if ($className && class_exists($className)) {
                    $name = basename($file, '.php');
                    
                    switch ($type) {
                        case 'types':
                            $this->types[$name] = $className;
                            break;
                        case 'queries':
                            $this->queries[$name] = $className;
                            break;
                        case 'mutations':
                            $this->mutations[$name] = $className;
                            break;
                    }
                }
            }
        }
    }

    /**
     * Extrait le nom de classe complet depuis un fichier
     *
     * @param string $file Chemin vers le fichier
     * @return string|null Nom de classe complet ou null
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);
        
        // Extraire le namespace
        if (preg_match('/namespace\s+([^;]+)/i', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];
        } else {
            $namespace = '';
        }
        
        // Extraire le nom de classe
        if (preg_match('/class\s+([^\s]+)/i', $content, $classMatches)) {
            $className = $classMatches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }
        
        return null;
    }

    /**
     * Traite une requête GraphQL
     *
     * @param \Nexa\Http\Request $request Requête HTTP
     * @param string $schema Nom du schéma à utiliser
     * @return \Nexa\Http\Response
     */
    public function handleRequest(Request $request, string $schema = 'default'): Response
    {
        try {
            // Vérifier si GraphQL est activé
            if (!$this->app['config']->get('graphql.enabled', true)) {
                return $this->createErrorResponse('GraphQL is disabled', 503);
            }

            // Vérifier si le schéma existe
            if (!isset($this->schemas[$schema])) {
                return $this->createErrorResponse('Schema not found', 404);
            }

            // Extraire la requête GraphQL
            $input = $this->parseRequest($request);
            
            if (!$input) {
                return $this->createErrorResponse('Invalid GraphQL request', 400);
            }

            // Appliquer le middleware
            $middlewareResponse = $this->applyMiddleware($request, $schema);
            if ($middlewareResponse) {
                return $middlewareResponse;
            }

            // Valider la requête
            if ($this->app['config']->get('graphql.validation.enabled', true)) {
                $validationResult = $this->validateQuery($input['query'], $schema);
                if ($validationResult !== true) {
                    return $this->createErrorResponse($validationResult, 400);
                }
            }

            // Exécuter la requête
            $result = $this->executeQuery(
                $input['query'],
                $input['variables'] ?? null,
                $input['operationName'] ?? null,
                $schema
            );

            return $this->createResponse($result);
            
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $this->formatError($e),
                500
            );
        }
    }

    /**
     * Parse la requête HTTP pour extraire les données GraphQL
     *
     * @param \Nexa\Http\Request $request Requête HTTP
     * @return array|null Données GraphQL ou null si invalide
     */
    protected function parseRequest(Request $request): ?array
    {
        if ($request->isMethod('GET')) {
            return [
                'query' => $request->query('query'),
                'variables' => $request->query('variables') ? json_decode($request->query('variables'), true) : null,
                'operationName' => $request->query('operationName')
            ];
        }
        
        if ($request->isMethod('POST')) {
            $contentType = $request->header('Content-Type');
            
            if (strpos($contentType, 'application/json') !== false) {
                return $request->json()->all();
            }
            
            if (strpos($contentType, 'application/graphql') !== false) {
                return ['query' => $request->getContent()];
            }
            
            // Form data
            return [
                'query' => $request->input('query'),
                'variables' => $request->input('variables') ? json_decode($request->input('variables'), true) : null,
                'operationName' => $request->input('operationName')
            ];
        }
        
        return null;
    }

    /**
     * Applique le middleware au schéma spécifié
     *
     * @param \Nexa\Http\Request $request Requête HTTP
     * @param string $schema Nom du schéma
     * @return \Nexa\Http\Response|null Réponse si le middleware bloque, null sinon
     */
    protected function applyMiddleware(Request $request, string $schema): ?Response
    {
        $middleware = $this->schemas[$schema]['middleware'] ?? [];
        
        foreach ($middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                
                if (method_exists($middlewareInstance, 'handle')) {
                    $result = $middlewareInstance->handle($request, function() {});
                    
                    if ($result instanceof Response) {
                        return $result;
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Valide une requête GraphQL
     *
     * @param string $query Requête GraphQL
     * @param string $schema Nom du schéma
     * @return bool|string True si valide, message d'erreur sinon
     */
    protected function validateQuery(string $query, string $schema)
    {
        // Vérifier la complexité de la requête
        if ($this->app['config']->get('graphql.security.limit_query_complexity', true)) {
            $maxComplexity = $this->app['config']->get('graphql.security.max_query_complexity', 100);
            $complexity = $this->calculateQueryComplexity($query);
            
            if ($complexity > $maxComplexity) {
                return "Query complexity ({$complexity}) exceeds maximum allowed ({$maxComplexity})";
            }
        }

        // Vérifier la profondeur de la requête
        if ($this->app['config']->get('graphql.security.limit_query_depth', true)) {
            $maxDepth = $this->app['config']->get('graphql.security.max_query_depth', 10);
            $depth = $this->calculateQueryDepth($query);
            
            if ($depth > $maxDepth) {
                return "Query depth ({$depth}) exceeds maximum allowed ({$maxDepth})";
            }
        }

        return true;
    }

    /**
     * Calcule la complexité d'une requête GraphQL
     *
     * @param string $query Requête GraphQL
     * @return int Complexité calculée
     */
    protected function calculateQueryComplexity(string $query): int
    {
        // Implémentation simplifiée - compter les champs
        return substr_count($query, '{') + substr_count($query, '}');
    }

    /**
     * Calcule la profondeur d'une requête GraphQL
     *
     * @param string $query Requête GraphQL
     * @return int Profondeur calculée
     */
    protected function calculateQueryDepth(string $query): int
    {
        // Implémentation simplifiée - compter les niveaux d'imbrication
        $depth = 0;
        $maxDepth = 0;
        
        for ($i = 0; $i < strlen($query); $i++) {
            if ($query[$i] === '{') {
                $depth++;
                $maxDepth = max($maxDepth, $depth);
            } elseif ($query[$i] === '}') {
                $depth--;
            }
        }
        
        return $maxDepth;
    }

    /**
     * Exécute une requête GraphQL
     *
     * @param string $query Requête GraphQL
     * @param array|null $variables Variables de la requête
     * @param string|null $operationName Nom de l'opération
     * @param string $schema Nom du schéma
     * @return array Résultat de l'exécution
     */
    protected function executeQuery(string $query, ?array $variables, ?string $operationName, string $schema): array
    {
        // Cette méthode devrait utiliser une bibliothèque GraphQL comme webonyx/graphql-php
        // Pour l'instant, on retourne un résultat simulé
        return [
            'data' => [
                'message' => 'GraphQL query executed successfully',
                'schema' => $schema,
                'query' => $query
            ]
        ];
    }

    /**
     * Formate une erreur pour la réponse
     *
     * @param \Exception $exception Exception à formater
     * @return array Erreur formatée
     */
    protected function formatError(\Exception $exception): array
    {
        $error = [
            'message' => $exception->getMessage()
        ];

        if ($this->app['config']->get('graphql.error_handling.debug', false)) {
            $error['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
            
            if ($this->app['config']->get('graphql.error_handling.include_trace', false)) {
                $error['trace'] = $exception->getTraceAsString();
            }
            
            if ($this->app['config']->get('graphql.error_handling.include_exception', false)) {
                $error['exception'] = get_class($exception);
            }
        }

        return $error;
    }

    /**
     * Crée une réponse GraphQL
     *
     * @param array $data Données de la réponse
     * @return \Nexa\Http\Response
     */
    protected function createResponse(array $data): Response
    {
        return new Response(json_encode($data), 200, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Crée une réponse d'erreur
     *
     * @param string|array $error Message d'erreur ou erreur formatée
     * @param int $status Code de statut HTTP
     * @return \Nexa\Http\Response
     */
    protected function createErrorResponse($error, int $status): Response
    {
        $data = [
            'errors' => [
                is_array($error) ? $error : ['message' => $error]
            ]
        ];

        return new Response(json_encode($data), $status, [
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Génère l'interface GraphiQL
     *
     * @param string $endpoint URL de l'endpoint GraphQL
     * @return string HTML de l'interface GraphiQL
     */
    public function generateGraphiQL(string $endpoint): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <title>GraphiQL</title>
    <link href="https://unpkg.com/graphiql/graphiql.min.css" rel="stylesheet" />
</head>
<body style="margin: 0;">
    <div id="graphiql" style="height: 100vh;"></div>
    <script crossorigin src="https://unpkg.com/react@17/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@17/umd/react-dom.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/graphiql/graphiql.min.js"></script>
    <script>
        const fetcher = GraphiQL.createFetcher({
            url: "' . $endpoint . '",
        });
        
        ReactDOM.render(
            React.createElement(GraphiQL, { fetcher: fetcher }),
            document.getElementById("graphiql")
        );
    </script>
</body>
</html>';
    }

    /**
     * Enregistre un type GraphQL
     *
     * @param string $name Nom du type
     * @param string $class Classe du type
     * @return void
     */
    public function registerType(string $name, string $class): void
    {
        $this->types[$name] = $class;
    }

    /**
     * Enregistre une requête GraphQL
     *
     * @param string $name Nom de la requête
     * @param string $class Classe de la requête
     * @return void
     */
    public function registerQuery(string $name, string $class): void
    {
        $this->queries[$name] = $class;
    }

    /**
     * Enregistre une mutation GraphQL
     *
     * @param string $name Nom de la mutation
     * @param string $class Classe de la mutation
     * @return void
     */
    public function registerMutation(string $name, string $class): void
    {
        $this->mutations[$name] = $class;
    }
}