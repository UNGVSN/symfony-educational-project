<?php

declare(strict_types=1);

namespace Command;

use Console\Command\AsCommand;
use Console\Command\Command;
use Console\Input\InputArgument;
use Console\Input\InputInterface;
use Console\Input\InputOption;
use Console\Output\OutputInterface;
use Console\Style\SymfonyStyle;

/**
 * Greet Command
 *
 * Example command that greets a user with various options.
 *
 * Usage:
 *   php bin/console app:greet John
 *   php bin/console app:greet John --uppercase
 *   php bin/console app:greet John --yell
 */
#[AsCommand(
    name: 'app:greet',
    description: 'Greets a user',
    aliases: ['greet']
)]
class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('This command allows you to greet a user with various formatting options.')
            ->addArgument('name', InputArgument::REQUIRED, 'Who do you want to greet?')
            ->addArgument('last-name', InputArgument::OPTIONAL, 'Last name (optional)')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Uppercase the greeting')
            ->addOption('yell', 'y', InputOption::VALUE_NONE, 'Add exclamation marks')
            ->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of times to greet', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get arguments
        $name = $input->getArgument('name');
        $lastName = $input->getArgument('last-name');

        if ($lastName) {
            $name .= ' ' . $lastName;
        }

        // Get options
        $uppercase = $input->getOption('uppercase');
        $yell = $input->getOption('yell');
        $iterations = (int) $input->getOption('iterations');

        // Build greeting
        $greeting = sprintf('Hello, %s', $name);

        if ($uppercase) {
            $greeting = strtoupper($greeting);
        }

        if ($yell) {
            $greeting .= '!!!';
        } else {
            $greeting .= '!';
        }

        // Display greeting
        $io->title('Greeting Command');

        for ($i = 0; $i < $iterations; $i++) {
            $io->text($greeting);
        }

        $io->newLine();
        $io->success('Greeting completed successfully!');

        return Command::SUCCESS;
    }
}
