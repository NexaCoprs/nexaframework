<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';

    protected function configure()
    {
        $this
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration')
            ->addOption('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created')
            ->addOption('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate');
    }

    protected function handle()
    {
        $name = $this->input->getArgument('name');
        $table = $this->input->getOption('table');
        $create = $this->input->getOption('create') ?: false;

        $className = $this->getClassName($name);
        $path = $this->getPath($className);

        if (file_exists($path)) {
            $this->error("Migration {$className} already exists!");
            return 1;
        }

        $this->ensureDirectoryExists(dirname($path));

        $stub = $this->getStub();
        $stub = str_replace('DummyClass', $className, $stub);
        $stub = str_replace('DummyTable', $table ?: 'your_table_name', $stub);

        if ($create !== false) {
            $stub = str_replace('// up method content', '$this->createTable(\''.$create.'\', function($table) {'."\n            // Add columns here\n        ".'});', $stub);
            $stub = str_replace('// down method content', '$this->dropTable(\''.$create.'\');', $stub);
        } else {
            $stub = str_replace('// up method content', '// Modify table structure here', $stub);
            $stub = str_replace('// down method content', '// Reverse the changes', $stub);
        }

        file_put_contents($path, $stub);

        $this->info("Migration [{$path}] created successfully.");
        return 0;
    }

    protected function getClassName($name)
    {
        return 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . 'Table';
    }

    protected function getPath($className)
    {
        $timestamp = date('Y_m_d_His');
        return $this->databasePath("migrations/{$timestamp}_{$className}.php");
    }

    protected function databasePath($path = '')
    {
        return dirname(__DIR__, 4) . '/database' . ($path ? '/' . ltrim($path, '/') : '');
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

use Nexa\Database\Migration;

class DummyClass extends Migration
{
    public function up()
    {
        // up method content
    }

    public function down()
    {
        // down method content
    }
}
EOT;
    }
}