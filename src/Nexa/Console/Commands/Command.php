<?php

namespace Nexa\Console\Commands;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->handle($input, $output);
    }

    abstract protected function handle(InputInterface $input, OutputInterface $output);

    protected function info($message, OutputInterface $output)
    {
        $output->writeln("<info>{$message}</info>");
    }

    protected function error($message, OutputInterface $output)
    {
        $output->writeln("<error>{$message}</error>");
    }

    protected function line($message, OutputInterface $output)
    {
        $output->writeln($message);
    }
}