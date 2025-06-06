<?php

namespace App\Http\Controllers;

use Nexa\View\TemplateEngine;
use Nexa\Validation\ValidatesRequests;
use Nexa\Http\Request;
use Nexa\Core\Logger;
use Nexa\Core\Cache;

class WelcomeController
{
    use ValidatesRequests;
    public function index()
    {
        // Log de la requête
        Logger::info('Welcome page accessed', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Utiliser le cache pour les données de la page d'accueil
        $welcomeData = Cache::remember('welcome_data', function() {
            return [
                'title' => 'Bienvenue sur Nexa Framework',
                'version' => '1.0.0',
                'features' => [
                    'Validation robuste',
                    'Système de cache',
                    'Logging avancé',
                    'Relations de base de données',
                    'Middleware de sécurité'
                ]
            ];
        }, 3600); // Cache pour 1 heure
        
        $templateEngine = new TemplateEngine(resource_path('views'), storage_path('framework/views'));
        return $templateEngine->render('welcome', $welcomeData);
    }

    public function about()
    {
        Logger::info('About page accessed');
        
        $templateEngine = new TemplateEngine(resource_path('views'), storage_path('framework/views'));
        return $templateEngine->render('about');
    }

    public function documentation()
    {
        Logger::info('Documentation page accessed');
        
        $templateEngine = new TemplateEngine(resource_path('views'), storage_path('framework/views'));
        return $templateEngine->render('documentation');
    }
    
    /**
     * Exemple d'utilisation de la validation
     */
    public function contact(Request $request)
    {
        if ($request->isPost()) {
            try {
                $validatedData = $this->validate($request->all(), [
                    'name' => 'required|min:2|max:50',
                    'email' => 'required|email',
                    'message' => 'required|min:10|max:1000'
                ]);
                
                Logger::info('Contact form submitted', $validatedData);
                
                // Traiter le formulaire de contact
                // ...
                
                return 'Merci pour votre message!';
                
            } catch (\Nexa\Validation\ValidationException $e) {
                Logger::warning('Contact form validation failed', $e->getErrors());
                
                $templateEngine = new TemplateEngine(resource_path('views'), storage_path('framework/views'));
                return $templateEngine->render('contact', [
                    'errors' => $e->getErrors(),
                    'old' => $request->all()
                ]);
            }
        }
        
        $templateEngine = new TemplateEngine(resource_path('views'), storage_path('framework/views'));
        return $templateEngine->render('contact');
    }
}