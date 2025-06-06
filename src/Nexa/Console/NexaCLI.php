<?php

namespace Nexa\Console;

use Nexa\Testing\TestRunner;
use Nexa\Queue\QueueManager;
use Nexa\Core\Logger;
use Nexa\Auth\JWTManager;

class NexaCLI
{
    private $commands = [];
    private $logger;
    
    public function __construct()
    {
        $this->logger = new Logger();
        $this->registerCommands();
    }

    /**
     * Register all available commands
     */
    private function registerCommands()
    {
        $this->commands = [
            'help' => [
                'description' => 'Show available commands',
                'usage' => 'nexa help [command]',
                'handler' => [$this, 'showHelp']
            ],
            'test' => [
                'description' => 'Run tests',
                'usage' => 'nexa test [--verbose] [--filter=pattern] [--report=format]',
                'handler' => [$this, 'runTests']
            ],
            'queue:work' => [
                'description' => 'Start processing queue jobs',
                'usage' => 'nexa queue:work [--queue=name] [--timeout=seconds] [--memory=MB]',
                'handler' => [$this, 'processQueue']
            ],
            'queue:status' => [
                'description' => 'Show queue status',
                'usage' => 'nexa queue:status [--queue=name]',
                'handler' => [$this, 'queueStatus']
            ],
            'queue:clear' => [
                'description' => 'Clear all jobs from queue',
                'usage' => 'nexa queue:clear [--queue=name]',
                'handler' => [$this, 'clearQueue']
            ],
            'queue:retry' => [
                'description' => 'Retry failed jobs',
                'usage' => 'nexa queue:retry [--queue=name] [--id=job_id]',
                'handler' => [$this, 'retryFailedJobs']
            ],
            'migrate' => [
                'description' => 'Run database migrations',
                'usage' => 'nexa migrate [--rollback] [--step=number]',
                'handler' => [$this, 'runMigrations']
            ],
            'migrate:create' => [
                'description' => 'Create a new migration file',
                'usage' => 'nexa migrate:create migration_name',
                'handler' => [$this, 'createMigration']
            ],
            'serve' => [
                'description' => 'Start development server',
                'usage' => 'nexa serve [--host=127.0.0.1] [--port=8000]',
                'handler' => [$this, 'startServer']
            ],
            'jwt:generate' => [
                'description' => 'Generate JWT secret key',
                'usage' => 'nexa jwt:generate',
                'handler' => [$this, 'generateJWTSecret']
            ],
            'cache:clear' => [
                'description' => 'Clear application cache',
                'usage' => 'nexa cache:clear',
                'handler' => [$this, 'clearCache']
            ],
            'logs:clear' => [
                'description' => 'Clear application logs',
                'usage' => 'nexa logs:clear [--level=error|warning|info]',
                'handler' => [$this, 'clearLogs']
            ],
            'make:controller' => [
                'description' => 'Create a new controller',
                'usage' => 'nexa make:controller ControllerName [--resource]',
                'handler' => [$this, 'makeController']
            ],
            'make:model' => [
                'description' => 'Create a new model',
                'usage' => 'nexa make:model ModelName [--migration]',
                'handler' => [$this, 'makeModel']
            ],
            'make:middleware' => [
                'description' => 'Create a new middleware',
                'usage' => 'nexa make:middleware MiddlewareName',
                'handler' => [$this, 'makeMiddleware']
            ],
            'make:job' => [
                'description' => 'Create a new queue job',
                'usage' => 'nexa make:job JobName',
                'handler' => [$this, 'makeJob']
            ],
            'make:listener' => [
                'description' => 'Create a new event listener',
                'usage' => 'nexa make:listener ListenerName [--event=EventName]',
                'handler' => [$this, 'makeListener']
            ],
            'make:test' => [
                'description' => 'Create a new test class',
                'usage' => 'nexa make:test TestName [--unit]',
                'handler' => [$this, 'makeTest']
            ],
            'env:check' => [
                'description' => 'Check environment configuration',
                'usage' => 'nexa env:check',
                'handler' => [$this, 'checkEnvironment']
            ],
            'version' => [
                'description' => 'Show Nexa Framework version',
                'usage' => 'nexa version',
                'handler' => [$this, 'showVersion']
            ]
        ];
    }

    /**
     * Run the CLI with given arguments
     */
    public function run($argv)
    {
        // Remove script name
        array_shift($argv);
        
        if (empty($argv)) {
            $this->showHelp();
            return;
        }
        
        $command = array_shift($argv);
        $args = $this->parseArguments($argv);
        
        if (!isset($this->commands[$command])) {
            $this->output("Unknown command: $command", 'red');
            $this->output("Run 'nexa help' to see available commands.");
            return;
        }
        
        try {
            call_user_func($this->commands[$command]['handler'], $args);
        } catch (\Exception $e) {
            $this->output("Error: " . $e->getMessage(), 'red');
            $this->logger->error('CLI command failed', [
                'command' => $command,
                'args' => $args,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Parse command line arguments
     */
    private function parseArguments($argv)
    {
        $args = [
            'options' => [],
            'parameters' => []
        ];
        
        foreach ($argv as $arg) {
            if (strpos($arg, '--') === 0) {
                $parts = explode('=', substr($arg, 2), 2);
                $args['options'][$parts[0]] = isset($parts[1]) ? $parts[1] : true;
            } else {
                $args['parameters'][] = $arg;
            }
        }
        
        return $args;
    }

    /**
     * Show help information
     */
    private function showHelp($args = [])
    {
        if (!empty($args['parameters'])) {
            $command = $args['parameters'][0];
            if (isset($this->commands[$command])) {
                $cmd = $this->commands[$command];
                $this->output("\nCommand: $command", 'yellow');
                $this->output("Description: {$cmd['description']}");
                $this->output("Usage: {$cmd['usage']}");
                return;
            }
        }
        
        $this->output("\n" . str_repeat('=', 50));
        $this->output("NEXA FRAMEWORK CLI", 'yellow');
        $this->output(str_repeat('=', 50));
        $this->output("\nAvailable commands:\n");
        
        foreach ($this->commands as $name => $command) {
            $this->output(sprintf("  %-20s %s", $name, $command['description']));
        }
        
        $this->output("\nFor detailed help on a command, use: nexa help <command>\n");
    }

    /**
     * Run tests
     */
    private function runTests($args)
    {
        $this->output("Starting test runner...", 'yellow');
        
        $runner = new TestRunner(isset($args['options']['verbose']));
        
        // Discover tests
        $testDir = __DIR__ . '/../../../tests';
        if (is_dir($testDir)) {
            $runner->discoverTests($testDir, 'Tests');
        }
        
        // Run tests
        $results = $runner->run();
        
        // Generate report if requested
        if (isset($args['options']['report'])) {
            $format = $args['options']['report'];
            $filename = "test_report_" . date('Y-m-d_H-i-s') . ".$format";
            $runner->generateReport($format, $filename);
        }
        
        // Exit with appropriate code
        exit($runner->allTestsPassed() ? 0 : 1);
    }

    /**
     * Process queue jobs
     */
    private function processQueue($args)
    {
        $queue = $args['options']['queue'] ?? 'default';
        $timeout = (int)($args['options']['timeout'] ?? 60);
        $memory = (int)($args['options']['memory'] ?? 128);
        
        $this->output("Starting queue worker for queue: $queue", 'green');
        $this->output("Timeout: {$timeout}s, Memory limit: {$memory}MB");
        
        $queueManager = new QueueManager();
        
        // Set memory limit
        ini_set('memory_limit', $memory . 'M');
        
        // Process jobs
        $queueManager->work($queue, $timeout);
    }

    /**
     * Show queue status
     */
    private function queueStatus($args)
    {
        $queue = $args['options']['queue'] ?? 'default';
        
        $queueManager = new QueueManager();
        $driver = $queueManager->getDriver();
        
        $size = $driver->size($queue);
        $failedJobs = $driver instanceof \Nexa\Queue\DatabaseQueueDriver 
            ? $driver->getFailedJobs($queue) 
            : [];
        
        $this->output("\nQueue Status: $queue", 'yellow');
        $this->output(str_repeat('-', 30));
        $this->output("Pending jobs: $size");
        $this->output("Failed jobs: " . count($failedJobs));
        
        if (!empty($failedJobs)) {
            $this->output("\nRecent failed jobs:");
            foreach (array_slice($failedJobs, 0, 5) as $job) {
                $this->output("  - {$job['job_name']} (ID: {$job['id']}) - {$job['failed_at']}");
            }
        }
    }

    /**
     * Clear queue
     */
    private function clearQueue($args)
    {
        $queue = $args['options']['queue'] ?? 'default';
        
        $queueManager = new QueueManager();
        $driver = $queueManager->getDriver();
        
        $driver->clear($queue);
        
        $this->output("Queue '$queue' cleared successfully.", 'green');
    }

    /**
     * Retry failed jobs
     */
    private function retryFailedJobs($args)
    {
        $queue = $args['options']['queue'] ?? 'default';
        $jobId = $args['options']['id'] ?? null;
        
        $queueManager = new QueueManager();
        
        $retried = $queueManager->retryFailedJobs($queue, $jobId);
        $this->output("Retried $retried failed job(s).", 'green');
    }

    /**
     * Start development server
     */
    private function startServer($args)
    {
        $host = $args['options']['host'] ?? '127.0.0.1';
        $port = $args['options']['port'] ?? '8000';
        
        $this->output("Starting development server...", 'yellow');
        $this->output("Server running at http://$host:$port", 'green');
        $this->output("Press Ctrl+C to stop the server");
        
        // Start PHP built-in server
        $command = "php -S $host:$port -t public";
        passthru($command);
    }

    /**
     * Generate JWT secret
     */
    private function generateJWTSecret($args)
    {
        $secret = bin2hex(random_bytes(32));
        
        $this->output("Generated JWT secret:", 'yellow');
        $this->output($secret, 'green');
        $this->output("\nAdd this to your .env file:");
        $this->output("JWT_SECRET=$secret");
        
        // Try to update .env file
        $envFile = '.env';
        if (file_exists($envFile)) {
            $content = file_get_contents($envFile);
            if (strpos($content, 'JWT_SECRET=') !== false) {
                $content = preg_replace('/JWT_SECRET=.*/', "JWT_SECRET=$secret", $content);
            } else {
                $content .= "\nJWT_SECRET=$secret\n";
            }
            file_put_contents($envFile, $content);
            $this->output("\n.env file updated successfully!", 'green');
        }
    }

    /**
     * Clear cache
     */
    private function clearCache($args)
    {
        $cacheDir = 'storage/cache';
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $this->output("Cache cleared successfully.", 'green');
        } else {
            $this->output("Cache directory not found.", 'yellow');
        }
    }

    /**
     * Clear logs
     */
    private function clearLogs($args)
    {
        $level = $args['options']['level'] ?? null;
        $logDir = 'storage/logs';
        
        if (!is_dir($logDir)) {
            $this->output("Log directory not found.", 'yellow');
            return;
        }
        
        $pattern = $level ? "$logDir/$level.log" : "$logDir/*.log";
        $files = glob($pattern);
        
        foreach ($files as $file) {
            file_put_contents($file, '');
        }
        
        $count = count($files);
        $this->output("Cleared $count log file(s).", 'green');
    }

    /**
     * Create controller
     */
    private function makeController($args)
    {
        if (empty($args['parameters'])) {
            $this->output("Controller name is required.", 'red');
            return;
        }
        
        $name = $args['parameters'][0];
        $isResource = isset($args['options']['resource']);
        
        $this->createFile('controller', $name, $isResource);
    }

    /**
     * Create model
     */
    private function makeModel($args)
    {
        if (empty($args['parameters'])) {
            $this->output("Model name is required.", 'red');
            return;
        }
        
        $name = $args['parameters'][0];
        $withMigration = isset($args['options']['migration']);
        
        $this->createFile('model', $name);
        
        if ($withMigration) {
            $this->createMigration(['parameters' => ['create_' . strtolower($name) . 's_table']]);
        }
    }

    /**
     * Create file from template
     */
    private function createFile($type, $name, $extra = false)
    {
        $templates = [
            'controller' => $this->getControllerTemplate($name, $extra),
            'model' => $this->getModelTemplate($name),
            'middleware' => $this->getMiddlewareTemplate($name),
            'job' => $this->getJobTemplate($name),
            'listener' => $this->getListenerTemplate($name),
            'test' => $this->getTestTemplate($name)
        ];
        
        if (!isset($templates[$type])) {
            $this->output("Unknown file type: $type", 'red');
            return;
        }
        
        $dirs = [
            'controller' => 'app/Http/Controllers',
            'model' => 'app/Models',
            'middleware' => 'app/Http/Middleware',
            'job' => 'app/Jobs',
            'listener' => 'app/Listeners',
            'test' => 'tests'
        ];
        
        $dir = $dirs[$type];
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $filename = "$dir/$name.php";
        
        if (file_exists($filename)) {
            $this->output("File already exists: $filename", 'yellow');
            return;
        }
        
        file_put_contents($filename, $templates[$type]);
        $this->output("Created: $filename", 'green');
    }

    /**
     * Get controller template
     */
    private function getControllerTemplate($name, $isResource = false)
    {
        $methods = $isResource ? 
            "\n    public function index()\n    {\n        //\n    }\n\n    public function create()\n    {\n        //\n    }\n\n    public function store()\n    {\n        //\n    }\n\n    public function show(\$id)\n    {\n        //\n    }\n\n    public function edit(\$id)\n    {\n        //\n    }\n\n    public function update(\$id)\n    {\n        //\n    }\n\n    public function destroy(\$id)\n    {\n        //\n    }" :
            "\n    public function index()\n    {\n        //\n    }";
        
        return "<?php\n\nnamespace App\\Http\\Controllers;\n\nuse Nexa\\Http\\Request;\nuse Nexa\\Http\\Response;\n\nclass {$name}\n{{$methods}\n}";
    }

    /**
     * Get model template
     */
    private function getModelTemplate($name)
    {
        $table = strtolower($name) . 's';
        return "<?php\n\nnamespace App\\Models;\n\nuse Nexa\\Database\\Model;\n\nclass {$name} extends Model\n{\n    protected \$table = '{$table}';\n    \n    protected \$fillable = [\n        //\n    ];\n}";
    }

    /**
     * Get middleware template
     */
    private function getMiddlewareTemplate($name)
    {
        return "<?php\n\nnamespace App\\Http\\Middleware;\n\nuse Nexa\\Http\\Request;\nuse Nexa\\Http\\Response;\n\nclass {$name}\n{\n    public function handle(Request \$request, \$next)\n    {\n        // Middleware logic here\n        \n        return \$next(\$request);\n    }\n}";
    }

    /**
     * Get job template
     */
    private function getJobTemplate($name)
    {
        return "<?php\n\nnamespace App\\Jobs;\n\nuse Nexa\\Queue\\Job;\n\nclass {$name} extends Job\n{\n    public function __construct(\$data = [])\n    {\n        parent::__construct(\$data);\n    }\n\n    public function handle()\n    {\n        // Job logic here\n    }\n}";
    }

    /**
     * Get listener template
     */
    private function getListenerTemplate($name)
    {
        return "<?php\n\nnamespace App\\Listeners;\n\nuse Nexa\\Events\\Listener;\nuse Nexa\\Events\\Event;\n\nclass {$name} extends Listener\n{\n    public function handle(Event \$event)\n    {\n        // Listener logic here\n    }\n\n    public function getEvents()\n    {\n        return [\n            // 'EventName'\n        ];\n    }\n}";
    }

    /**
     * Get test template
     */
    private function getTestTemplate($name)
    {
        return "<?php\n\nnamespace Tests;\n\nuse Nexa\\Testing\\TestCase;\n\nclass {$name} extends TestCase\n{\n    public function testExample()\n    {\n        \$this->assertTrue(true);\n    }\n}";
    }

    /**
     * Show version
     */
    private function showVersion($args)
    {
        $this->output("\nNexa Framework v2.0.0", 'yellow');
        $this->output("Phase 2 - Advanced Features");
        $this->output("PHP " . PHP_VERSION . "\n");
    }

    /**
     * Check environment
     */
    private function checkEnvironment($args)
    {
        $this->output("\nEnvironment Check", 'yellow');
        $this->output(str_repeat('-', 30));
        
        // Check PHP version
        $phpVersion = PHP_VERSION;
        $minPhp = '7.4.0';
        $phpOk = version_compare($phpVersion, $minPhp, '>=');
        $this->output("PHP Version: $phpVersion " . ($phpOk ? '✓' : '✗'), $phpOk ? 'green' : 'red');
        
        // Check extensions
        $extensions = ['pdo', 'json', 'mbstring', 'openssl'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->output("Extension $ext: " . ($loaded ? '✓' : '✗'), $loaded ? 'green' : 'red');
        }
        
        // Check directories
        $dirs = ['storage/logs', 'storage/cache', 'storage/framework/views'];
        foreach ($dirs as $dir) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            $status = $exists ? ($writable ? '✓ writable' : '⚠ not writable') : '✗ missing';
            $color = $exists ? ($writable ? 'green' : 'yellow') : 'red';
            $this->output("Directory $dir: $status", $color);
        }
        
        // Check .env file
        $envExists = file_exists('.env');
        $this->output(".env file: " . ($envExists ? '✓' : '✗'), $envExists ? 'green' : 'red');
        
        $this->output("");
    }

    /**
     * Output text with color
     */
    private function output($text, $color = null)
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'reset' => "\033[0m"
        ];
        
        if ($color && isset($colors[$color])) {
            echo $colors[$color] . $text . $colors['reset'] . "\n";
        } else {
            echo $text . "\n";
        }
    }

    // Placeholder methods for migration commands
    private function runMigrations($args) {
        $this->output("Migration system not yet implemented.", 'yellow');
    }
    
    private function createMigration($args) {
        $this->output("Migration creation not yet implemented.", 'yellow');
    }
    
    private function makeMiddleware($args) {
        if (empty($args['parameters'])) {
            $this->output("Middleware name is required.", 'red');
            return;
        }
        $this->createFile('middleware', $args['parameters'][0]);
    }
    
    private function makeJob($args) {
        if (empty($args['parameters'])) {
            $this->output("Job name is required.", 'red');
            return;
        }
        $this->createFile('job', $args['parameters'][0]);
    }
    
    private function makeListener($args) {
        if (empty($args['parameters'])) {
            $this->output("Listener name is required.", 'red');
            return;
        }
        $this->createFile('listener', $args['parameters'][0]);
    }
    
    private function makeTest($args) {
        if (empty($args['parameters'])) {
            $this->output("Test name is required.", 'red');
            return;
        }
        $this->createFile('test', $args['parameters'][0]);
    }
}