<?php

declare(strict_types=1);

namespace Console;

use Console\Command\Command;
use Console\Input\ArgvInput;
use Console\Input\InputInterface;
use Console\Output\ConsoleOutput;
use Console\Output\OutputInterface;

/**
 * Console Application
 *
 * Main entry point for CLI applications. Manages command registration,
 * input parsing, command discovery, and execution.
 */
class Application
{
    /** @var array<string, Command> */
    private array $commands = [];

    private string $name;
    private string $version;

    public function __construct(string $name = 'Console Application', string $version = '1.0.0')
    {
        $this->name = $name;
        $this->version = $version;
    }

    /**
     * Register a command
     */
    public function add(Command $command): Command
    {
        $this->commands[$command->getName()] = $command;

        // Register aliases
        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }

        return $command;
    }

    /**
     * Run the application
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        $input ??= new ArgvInput();
        $output ??= new ConsoleOutput();

        try {
            // Get the command name from input
            $commandName = $this->getCommandName($input);

            if ($commandName === null || $commandName === 'list') {
                return $this->listCommands($output);
            }

            if ($commandName === 'help') {
                return $this->showHelp($input, $output);
            }

            // Find and execute the command
            $command = $this->find($commandName);
            return $command->run($input, $output);

        } catch (\Throwable $e) {
            $this->renderException($e, $output);
            return Command::FAILURE;
        }
    }

    /**
     * Find a command by name
     *
     * @throws \InvalidArgumentException if command not found
     */
    public function find(string $name): Command
    {
        if (!isset($this->commands[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Command "%s" not found.', $name)
            );
        }

        return $this->commands[$name];
    }

    /**
     * Get all registered commands
     *
     * @return array<string, Command>
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Check if a command exists
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    /**
     * Get the command name from input
     */
    private function getCommandName(InputInterface $input): ?string
    {
        // In a real implementation, this would parse the first argument
        // For simplicity, we'll use a method on the input interface
        if (method_exists($input, 'getFirstArgument')) {
            return $input->getFirstArgument();
        }

        return null;
    }

    /**
     * List all available commands
     */
    private function listCommands(OutputInterface $output): int
    {
        $output->writeln(sprintf('<info>%s</info> version <comment>%s</comment>', $this->name, $this->version));
        $output->writeln('');
        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('  command [options] [arguments]');
        $output->writeln('');
        $output->writeln('<comment>Available commands:</comment>');

        // Group commands by namespace
        $namespaces = [];
        foreach ($this->commands as $name => $command) {
            // Skip aliases
            if ($command->getName() !== $name) {
                continue;
            }

            // Skip hidden commands
            if ($command->isHidden()) {
                continue;
            }

            $parts = explode(':', $name);
            $namespace = count($parts) > 1 ? $parts[0] : '_global';

            $namespaces[$namespace][] = $command;
        }

        foreach ($namespaces as $namespace => $commands) {
            if ($namespace !== '_global') {
                $output->writeln(sprintf(' <comment>%s</comment>', $namespace));
            }

            foreach ($commands as $command) {
                $output->writeln(sprintf(
                    '  <info>%-30s</info> %s',
                    $command->getName(),
                    $command->getDescription()
                ));
            }

            $output->writeln('');
        }

        return Command::SUCCESS;
    }

    /**
     * Show help for a command
     */
    private function showHelp(InputInterface $input, OutputInterface $output): int
    {
        // Get the command name after 'help'
        $commandName = $input->getArgument('command_name');

        if (!$commandName) {
            $output->writeln('<error>Please specify a command name</error>');
            return Command::INVALID;
        }

        try {
            $command = $this->find($commandName);
            $command->showHelp($output);
            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    /**
     * Render an exception
     */
    private function renderException(\Throwable $e, OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(sprintf('<error>  [%s]  </error>', get_class($e)));
        $output->writeln(sprintf('<error>  %s  </error>', $e->getMessage()));
        $output->writeln('');

        if ($output->isVerbose()) {
            $output->writeln('<comment>Exception trace:</comment>');
            $output->writeln($e->getTraceAsString());
            $output->writeln('');
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
