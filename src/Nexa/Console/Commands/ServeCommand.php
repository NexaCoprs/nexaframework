<?php

namespace Nexa\Console\Commands;

use Nexa\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends Command
{
    protected static $defaultName = 'serve';

    protected function configure()
    {
        $this
            ->setDescription('Serve the application on the PHP development server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', 'localhost')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on', '8000');
    }

    protected function handle()
    {
        $host = $this->input->getOption('host');
        $port = $this->input->getOption('port');

        $this->info("Nexa development server started on http://{$host}:{$port}");
        $this->info('Press Ctrl+C to stop the server');

        $publicPath = $this->getPublicPath();
        $command = sprintf('php -S %s:%s -t %s', $host, $port, escapeshellarg($publicPath));

        passthru($command);

        return 0;
    }

    protected function getPublicPath()
    {
        return dirname(__DIR__, 4) . '/public';
    }
}