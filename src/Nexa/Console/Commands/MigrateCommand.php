<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    protected function configure()
    {
        $this
            ->setDescription('Run the database migrations');
    }

    protected function handle()
    {
        $migrationsPath = $this->databasePath('migrations');
        $migrationFiles = glob($migrationsPath.'/*.php');

        if (empty($migrationFiles)) {
            $this->info('No migrations found.');
            return 0;
        }

        $this->info('Running migrations...');

        foreach ($migrationFiles as $file) {
            $className = $this->getMigrationClassName($file);
            require_once $file;

            $migration = new $className($this->getConnection());
            $migration->run();

            $this->line("Migrated: {$className}");
        }

        $this->info('Migrations completed successfully.');
        return 0;
    }

    protected function getMigrationClassName($file)
    {
        $fileName = basename($file, '.php');
        // Remove timestamp prefix (e.g., "2023_01_01_000000_")
        $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $fileName);
        // Convert snake_case to PascalCase
        $className = str_replace('_', '', ucwords($className, '_'));
        return $className;
    }

    protected function getConnection()
    {
        // Load .env file if not already loaded
        $envFile = dirname(__DIR__, 4) . '/.env';
        if (file_exists($envFile) && class_exists('\Dotenv\Dotenv')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 4));
            $dotenv->safeLoad();
        }
        
        // Return a PDO connection instance
        $host = $this->env('DB_HOST', 'localhost');
        $database = $this->env('DB_DATABASE', 'nexa');
        $username = $this->env('DB_USERNAME', 'root');
        $password = $this->env('DB_PASSWORD', '');
        
        try {
            return new \PDO(
                "mysql:host={$host};dbname={$database}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            $this->line('Please check your database configuration in .env file:');
            $this->line('DB_HOST=' . $host);
            $this->line('DB_DATABASE=' . $database);
            $this->line('DB_USERNAME=' . $username);
            $this->line('DB_PASSWORD=' . ($password ? '[SET]' : '[EMPTY]'));
            throw $e;
        }
    }

    protected function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $value;
    }

    protected function databasePath($path = '')
    {
        return dirname(__DIR__, 4) . '/database/' . ltrim($path, '/');
    }
}