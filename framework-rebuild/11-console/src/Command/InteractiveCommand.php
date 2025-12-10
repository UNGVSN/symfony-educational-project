<?php

declare(strict_types=1);

namespace Command;

use Console\Command\AsCommand;
use Console\Command\Command;
use Console\Input\InputInterface;
use Console\Output\OutputInterface;
use Console\Style\SymfonyStyle;

/**
 * Interactive Command
 *
 * Demonstrates interactive features like questions and confirmations.
 *
 * Usage:
 *   php bin/console app:interactive
 */
#[AsCommand(
    name: 'app:interactive',
    description: 'Interactive command demonstration'
)]
class InteractiveCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command demonstrates interactive features including questions, choices, and confirmations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Interactive Command Demo');

        // Simple question
        $io->section('Basic Questions');
        $name = $io->ask('What is your name?', 'Anonymous');
        $io->text(sprintf('Hello, %s!', $name));

        // Number validation
        $age = $io->ask('How old are you?');
        if (is_numeric($age)) {
            $io->text(sprintf('You are %d years old.', $age));
        }

        // Hidden input (password)
        $io->section('Hidden Input');
        $io->note('The next input will be hidden (like a password)');
        $password = $io->askHidden('Enter a secret password');
        $io->text('Password saved! (hidden)');

        // Confirmation
        $io->section('Confirmation');
        $confirmed = $io->confirm('Do you want to continue?', true);

        if (!$confirmed) {
            $io->warning('Operation cancelled by user');
            return Command::SUCCESS;
        }

        // Choice
        $io->section('Multiple Choice');
        $color = $io->choice(
            'What is your favorite color?',
            ['Red', 'Green', 'Blue', 'Yellow'],
            'Blue'
        );
        $io->text(sprintf('You selected: %s', $color));

        // List display
        $io->section('Your Selections');
        $io->listing([
            sprintf('Name: %s', $name),
            sprintf('Age: %s', $age),
            sprintf('Favorite Color: %s', $color),
        ]);

        // Final message
        $io->success('Interactive demo completed!');

        return Command::SUCCESS;
    }
}
