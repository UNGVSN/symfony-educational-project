<?php

declare(strict_types=1);

namespace Console\Command;

use Console\Input\InputArgument;
use Console\Input\InputInterface;
use Console\Input\InputOption;
use Console\Output\OutputInterface;

/**
 * Base Command class
 *
 * All console commands extend this class. Provides configuration,
 * execution lifecycle, and argument/option management.
 */
abstract class Command
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const INVALID = 2;

    private string $name = '';
    private string $description = '';
    private string $help = '';
    private bool $hidden = false;

    /** @var string[] */
    private array $aliases = [];

    /** @var array<string, InputArgument> */
    private array $arguments = [];

    /** @var array<string, InputOption> */
    private array $options = [];

    public function __construct()
    {
        $this->configure();

        // Read #[AsCommand] attribute if present
        $this->loadFromAttribute();
    }

    /**
     * Configure the command (override in subclasses)
     */
    protected function configure(): void
    {
        // Override in subclasses
    }

    /**
     * Initialize before execution (optional hook)
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Override in subclasses if needed
    }

    /**
     * Interact with the user (optional hook)
     * Called after initialize and before execute
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // Override in subclasses if needed
    }

    /**
     * Execute the command (must be implemented by subclasses)
     */
    abstract protected function execute(InputInterface $input, OutputInterface $output): int;

    /**
     * Run the command
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        // Bind input definitions
        $this->bindInput($input);

        // Validate input
        $this->validateInput($input);

        // Initialize
        $this->initialize($input, $output);

        // Interact
        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }

        // Execute
        return $this->execute($input, $output);
    }

    /**
     * Set command name
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get command name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set command description
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get command description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set help text
     */
    public function setHelp(string $help): static
    {
        $this->help = $help;
        return $this;
    }

    /**
     * Get help text
     */
    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * Set command aliases
     */
    public function setAliases(array $aliases): static
    {
        $this->aliases = $aliases;
        return $this;
    }

    /**
     * Get command aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Set hidden flag
     */
    public function setHidden(bool $hidden = true): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Check if command is hidden
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Add an argument
     */
    public function addArgument(
        string $name,
        int $mode = InputArgument::OPTIONAL,
        string $description = '',
        mixed $default = null
    ): static {
        $this->arguments[$name] = new InputArgument($name, $mode, $description, $default);
        return $this;
    }

    /**
     * Add an option
     */
    public function addOption(
        string $name,
        ?string $shortcut = null,
        int $mode = InputOption::VALUE_NONE,
        string $description = '',
        mixed $default = null
    ): static {
        $this->options[$name] = new InputOption($name, $shortcut, $mode, $description, $default);
        return $this;
    }

    /**
     * Get all arguments
     *
     * @return array<string, InputArgument>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get all options
     *
     * @return array<string, InputOption>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Show help for this command
     */
    public function showHelp(OutputInterface $output): void
    {
        $output->writeln(sprintf('<comment>Description:</comment>'));
        $output->writeln('  ' . $this->description);
        $output->writeln('');

        $output->writeln(sprintf('<comment>Usage:</comment>'));
        $output->writeln('  ' . $this->getSynopsis());
        $output->writeln('');

        if (!empty($this->arguments)) {
            $output->writeln('<comment>Arguments:</comment>');
            foreach ($this->arguments as $argument) {
                $output->writeln(sprintf(
                    '  <info>%-20s</info> %s%s',
                    $argument->getName(),
                    $argument->getDescription(),
                    $argument->getDefault() !== null ? sprintf(' [default: %s]', $argument->getDefault()) : ''
                ));
            }
            $output->writeln('');
        }

        if (!empty($this->options)) {
            $output->writeln('<comment>Options:</comment>');
            foreach ($this->options as $option) {
                $synopsis = '--' . $option->getName();
                if ($option->getShortcut()) {
                    $synopsis = '-' . $option->getShortcut() . ', ' . $synopsis;
                }

                $output->writeln(sprintf(
                    '  <info>%-20s</info> %s%s',
                    $synopsis,
                    $option->getDescription(),
                    $option->getDefault() !== null ? sprintf(' [default: %s]', $option->getDefault()) : ''
                ));
            }
            $output->writeln('');
        }

        if ($this->help) {
            $output->writeln('<comment>Help:</comment>');
            $output->writeln('  ' . $this->help);
            $output->writeln('');
        }
    }

    /**
     * Get command synopsis (usage pattern)
     */
    private function getSynopsis(): string
    {
        $synopsis = $this->name;

        if (!empty($this->options)) {
            $synopsis .= ' [options]';
        }

        foreach ($this->arguments as $argument) {
            $synopsis .= ' ';

            if ($argument->isRequired()) {
                $synopsis .= $argument->getName();
            } else {
                $synopsis .= '[' . $argument->getName() . ']';
            }

            if ($argument->isArray()) {
                $synopsis .= '...';
            }
        }

        return $synopsis;
    }

    /**
     * Bind input definitions
     */
    private function bindInput(InputInterface $input): void
    {
        // Set argument and option definitions
        if (method_exists($input, 'bind')) {
            $input->bind($this->arguments, $this->options);
        }
    }

    /**
     * Validate input
     */
    private function validateInput(InputInterface $input): void
    {
        // Check required arguments
        foreach ($this->arguments as $name => $argument) {
            if ($argument->isRequired() && !$input->hasArgument($name)) {
                throw new \RuntimeException(
                    sprintf('Required argument "%s" is missing', $name)
                );
            }
        }
    }

    /**
     * Load command configuration from #[AsCommand] attribute
     */
    private function loadFromAttribute(): void
    {
        $reflection = new \ReflectionClass($this);
        $attributes = $reflection->getAttributes(AsCommand::class);

        if (empty($attributes)) {
            return;
        }

        $attribute = $attributes[0]->newInstance();

        if ($attribute->name) {
            $this->name = $attribute->name;
        }

        if ($attribute->description) {
            $this->description = $attribute->description;
        }

        if ($attribute->hidden !== null) {
            $this->hidden = $attribute->hidden;
        }

        if (!empty($attribute->aliases)) {
            $this->aliases = $attribute->aliases;
        }
    }
}
