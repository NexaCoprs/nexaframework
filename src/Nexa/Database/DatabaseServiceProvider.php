<?php

namespace Nexa\Database;

use PDO;
use Nexa\Core\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('db', function () {
            $config = require $this->app->basePath('config/database.php');
            
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            Model::setConnection($pdo);
            
            return $pdo;
        });
    }

    public function boot()
    {
        // Boot logic if needed
    }
}