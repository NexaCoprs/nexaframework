<?php

namespace Nexa\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel
{
    protected $commands = [];

    public function __construct()
    {
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        // Register built-in commands
        $this->commands = [
            new Commands\MakeControllerCommand,
            new Commands\MakeModelCommand,
            new Commands\MakeMigrationCommand,
            new Commands\MigrateCommand,
            new Commands\ServeCommand,
        ];
    }

    public function handle($input = null, $output = null)
    {
        $application = new Application('Nexa Framework', '1.0.0');

        foreach ($this->commands as $command) {
            $application->add($command);
        }

        $application->run($input, $output);
    }

    public function command($signature, $description = '', $callback = null)
    {
        if ($callback instanceof \Closure) {
            $command = new class($signature, $description, $callback) extends Command {
                protected $callback;

                public function __construct($signature, $description, $callback)
                {
                    parent::__construct($signature);
                    $this->setDescription($description);
                    $this->callback = $callback;
                }

                protected function configure()
                {
                    $this->setName($this->getName());
                }

                protected function execute(InputInterface $input, OutputInterface $output)
                {
                    return ($this->callback)($input, $output);
                }
            };

            $this->commands[] = $command;
        }

        return $this;
    }
}