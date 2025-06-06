<?php

namespace Nexa\Console;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends BaseCommand
{
    protected $input;
    protected $output;

    abstract protected function handle();

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        return $this->handle();
    }

    protected function info($message)
    {
        $this->output->writeln("<info>{$message}</info>");
    }

    protected function error($message)
    {
        $this->output->writeln("<error>{$message}</error>");
    }

    protected function line($message)
    {
        $this->output->writeln($message);
    }
}