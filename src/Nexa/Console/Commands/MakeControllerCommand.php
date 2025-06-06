<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';

    protected function configure()
    {
        $this
            ->setDescription('Create a new controller class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller');
    }

    protected function handle()
    {
        $name = $this->input->getArgument('name');
        $className = $this->getClassName($name);
        $path = $this->getPath($className);

        if (file_exists($path)) {
            $this->error("Controller {$className} already exists!");
            return 1;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $stub = str_replace('DummyClass', $className, $stub);

        file_put_contents($path, $stub);

        $this->info("Controller [{$path}] created successfully.");
        return 0;
    }

    protected function getClassName($name)
    {
        return str_replace('/', '\\', ucfirst($name)) . 'Controller';
    }

    protected function getPath($className)
    {
        return $this->appPath('Http/Controllers/' . str_replace('\\', '/', $className) . '.php');
    }

    protected function appPath($path = '')
    {
        return dirname(__DIR__, 4) . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }

    protected function ensureDirectoryExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    protected function getStub()
    {
        return <<<'EOT'
<?php

namespace App\Http\Controllers;

use Nexa\Http\Controller;

class DummyClass extends Controller
{
    // Controller methods here
}
EOT;
    }
}