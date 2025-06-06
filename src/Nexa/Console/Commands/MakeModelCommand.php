<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Nexa\Console\Kernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModelCommand extends Command
{
    protected static $defaultName = 'make:model';

    protected function configure()
    {
        $this
            ->setDescription('Create a new model class')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model')
            ->addOption('migration', 'm', null, 'Create a new migration file for the model');
    }

    protected function handle()
    {
        $name = $this->input->getArgument('name');
        $className = $this->getClassName($name);
        $path = $this->getPath($className);

        if (file_exists($path)) {
            $this->error("Model {$className} already exists!");
            return 1;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $stub = str_replace('DummyClass', $className, $stub);
        $stub = str_replace('DummyTable', $this->getTableName($name), $stub);

        file_put_contents($path, $stub);

        $this->info("Model [{$path}] created successfully.");

        if ($this->input->getOption('migration')) {
            $this->call('make:migration', [
                'name' => "create_{$this->getTableName($name)}_table",
                '--create' => $this->getTableName($name)
            ]);
        }

        return 0;
    }

    protected function getClassName($name)
    {
        return str_replace('/', '\\', ucfirst($name));
    }

    protected function getPath($className)
    {
        return $this->appPath('Models/' . str_replace('\\', '/', $className) . '.php');
    }

    protected function appPath($path = '')
    {
        return dirname(__DIR__, 4) . '/app/' . ltrim($path, '/');
    }

    protected function getTableName($name)
    {
        return strtolower(str_replace('\\', '_', $this->getClassName($name)) . 's');
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

namespace App\Models;

use Nexa\Database\Model;

class DummyClass extends Model
{
    protected $table = 'DummyTable';
    
    protected $fillable = [
        // Fillable fields here
    ];
}
EOT;
    }

    protected function call($command, array $arguments = [])
    {
        $kernel = new Kernel($this->getApplication());
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        return $kernel->handle($input, $this->output);
    }
}