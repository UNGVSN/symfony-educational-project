<?php

declare(strict_types=1);

namespace Command;

use Console\Command\AsCommand;
use Console\Command\Command;
use Console\Input\InputInterface;
use Console\Input\InputOption;
use Console\Output\OutputInterface;
use Console\Style\SymfonyStyle;

/**
 * List Users Command
 *
 * Example command that demonstrates table output and progress bars.
 *
 * Usage:
 *   php bin/console app:list-users
 *   php bin/console app:list-users --format=json
 */
#[AsCommand(
    name: 'app:list-users',
    description: 'Lists all users',
    aliases: ['list:users']
)]
class ListUsersCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('This command displays a list of all users in the system.')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (table, json, csv)', 'table')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of results', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');
        $limit = (int) $input->getOption('limit');

        $io->title('User List');

        // Simulate fetching users
        $io->section('Fetching users from database...');

        $users = $this->fetchUsers($limit, $io);

        // Display based on format
        match ($format) {
            'json' => $this->displayJson($users, $output),
            'csv' => $this->displayCsv($users, $output),
            default => $this->displayTable($users, $io),
        };

        $io->newLine();
        $io->success(sprintf('Successfully displayed %d users', count($users)));

        return Command::SUCCESS;
    }

    /**
     * Fetch users (simulated with progress bar)
     */
    private function fetchUsers(int $limit, SymfonyStyle $io): array
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'User'],
            ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'role' => 'User'],
            ['id' => 4, 'name' => 'Alice Williams', 'email' => 'alice@example.com', 'role' => 'Moderator'],
            ['id' => 5, 'name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'role' => 'User'],
            ['id' => 6, 'name' => 'Diana Prince', 'email' => 'diana@example.com', 'role' => 'Admin'],
            ['id' => 7, 'name' => 'Eve Davis', 'email' => 'eve@example.com', 'role' => 'User'],
            ['id' => 8, 'name' => 'Frank Miller', 'email' => 'frank@example.com', 'role' => 'User'],
            ['id' => 9, 'name' => 'Grace Lee', 'email' => 'grace@example.com', 'role' => 'Moderator'],
            ['id' => 10, 'name' => 'Henry Wilson', 'email' => 'henry@example.com', 'role' => 'User'],
        ];

        $users = array_slice($users, 0, $limit);

        // Simulate slow operation with progress bar
        $io->progressStart(count($users));

        $result = [];
        foreach ($users as $user) {
            usleep(100000); // Sleep for 0.1 seconds
            $result[] = $user;
            $io->progressAdvance();
        }

        $io->progressFinish();

        return $result;
    }

    /**
     * Display as table
     */
    private function displayTable(array $users, SymfonyStyle $io): void
    {
        $io->section('Users Table');

        $io->table(
            ['ID', 'Name', 'Email', 'Role'],
            array_map(fn($user) => [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['role'],
            ], $users)
        );
    }

    /**
     * Display as JSON
     */
    private function displayJson(array $users, OutputInterface $output): void
    {
        $output->writeln(json_encode($users, JSON_PRETTY_PRINT));
    }

    /**
     * Display as CSV
     */
    private function displayCsv(array $users, OutputInterface $output): void
    {
        // Header
        $output->writeln('ID,Name,Email,Role');

        // Rows
        foreach ($users as $user) {
            $output->writeln(sprintf(
                '%d,"%s","%s","%s"',
                $user['id'],
                $user['name'],
                $user['email'],
                $user['role']
            ));
        }
    }
}
