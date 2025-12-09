# Console Component - Core Concepts

Deep dive into the fundamental concepts of Symfony's Console component.

---

## Table of Contents

1. [Console Component Architecture](#console-component-architecture)
2. [Command Lifecycle](#command-lifecycle)
3. [Creating Commands with Attributes](#creating-commands-with-attributes)
4. [Command Structure Methods](#command-structure-methods)
5. [Arguments in Detail](#arguments-in-detail)
6. [Options in Detail](#options-in-detail)
7. [Input and Output Objects](#input-and-output-objects)
8. [SymfonyStyle Component](#symfonystyle-component)
9. [Progress Bars](#progress-bars)
10. [Tables](#tables)
11. [Question Helper](#question-helper)
12. [Console Events](#console-events)
13. [Verbosity Levels](#verbosity-levels)
14. [Running Commands Programmatically](#running-commands-programmatically)
15. [Testing Commands](#testing-commands)

---

## Console Component Architecture

### Component Overview

The Symfony Console component is a standalone library that provides tools for creating command-line interfaces. It's used by Symfony's `bin/console` tool and can be used independently in any PHP project.

**Key Components:**

```
Console Component
├── Application           - Main console application
├── Command               - Base command class
├── Input                 - Input handling (arguments, options)
│   ├── InputInterface
│   ├── InputArgument
│   └── InputOption
├── Output                - Output handling (formatting, verbosity)
│   ├── OutputInterface
│   └── OutputFormatter
├── Helper                - UI helpers (progress bars, tables, questions)
│   ├── ProgressBar
│   ├── Table
│   └── QuestionHelper
├── Style                 - Output styling (SymfonyStyle)
└── Event                 - Console events
```

### The Application Class

```php
use Symfony\Component\Console\Application;

// Create a console application
$application = new Application('My Console App', '1.0.0');

// Add commands
$application->add(new MyCommand());

// Run the application
$application->run();
```

In Symfony applications, the Application is automatically configured and commands are auto-registered via dependency injection.

---

## Command Lifecycle

### Complete Execution Flow

```
1. Command Resolution
   ├── Parse command name from input
   ├── Find matching command in application
   └── Instantiate command (with dependencies)

2. Input Binding
   ├── Parse arguments from input string
   ├── Parse options from input string
   ├── Bind values to InputDefinition
   └── Validate required arguments/options

3. Input Validation
   ├── Check required arguments are provided
   ├── Validate argument types and formats
   └── Throw exception if validation fails

4. Initialization (initialize method)
   ├── Called before interaction and execution
   ├── Used for validation and setup
   └── Can modify input

5. Interaction (interact method)
   ├── Called if input is interactive
   ├── Ask for missing required arguments
   ├── Prompt user for additional input
   └── Can modify input

6. Execution (execute method)
   ├── Main command logic
   ├── Process input
   ├── Generate output
   └── Return exit code

7. Termination
   ├── Cleanup resources
   ├── Trigger terminate event
   └── Return exit code to shell
```

### Lifecycle Methods

```php
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LifecycleCommand extends Command
{
    /**
     * Called once before initialize()
     * Use for configuration
     */
    protected function configure(): void
    {
        // Define command name, description, arguments, options
    }

    /**
     * Called before interact() and execute()
     * Use for validation and initialization
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Validate input
        // Initialize resources
        // Cannot ask questions here
    }

    /**
     * Called after initialize() but before execute()
     * Only called if input is interactive (not with --no-interaction)
     * Use for asking questions to complete missing input
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // Ask questions
        // Fill in missing arguments/options
        // Modify input
    }

    /**
     * Main command logic
     * Must return an integer exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Main command logic
        return Command::SUCCESS;
    }
}
```

### Example with All Lifecycle Methods

```php
#[AsCommand(name: 'app:user:create')]
class CreateUserCommand extends Command
{
    private array $availableRoles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_EDITOR'];

    protected function configure(): void
    {
        $this
            ->setDescription('Create a new user')
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email address')
            ->addOption('role', 'r', InputOption::VALUE_OPTIONAL, 'User role', 'ROLE_USER')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Validate role
        $role = $input->getOption('role');
        if (!in_array($role, $this->availableRoles)) {
            throw new \InvalidArgumentException(
                sprintf('Role must be one of: %s', implode(', ', $this->availableRoles))
            );
        }

        // Validate username format
        $username = $input->getArgument('username');
        if (preg_match('/[^a-z0-9_-]/i', $username)) {
            throw new \InvalidArgumentException('Username can only contain letters, numbers, hyphens and underscores');
        }
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        // Ask for email if not provided
        if (!$input->getArgument('email')) {
            $email = $io->ask('Email address', null, function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email address');
                }
                return $answer;
            });
            $input->setArgument('email', $email);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $role = $input->getOption('role');

        // Create user...
        $io->success(sprintf('User "%s" created with role %s', $username, $role));

        return Command::SUCCESS;
    }
}
```

---

## Creating Commands with Attributes

### Modern PHP 8+ Attribute Syntax

Since Symfony 6.1, the recommended way to create commands is using the `#[AsCommand]` attribute:

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(
    name: 'app:my-command',
    description: 'This is my command description',
    aliases: ['my-cmd', 'mycmd'],
    hidden: false
)]
class MyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
```

### Attribute Parameters

```php
#[AsCommand(
    name: 'app:command',           // Command name (required)
    description: 'Description',     // Short description
    aliases: ['alias1', 'alias2'], // Command aliases
    hidden: false                   // Hide from command list
)]
```

### Dependency Injection in Commands

Commands are services, so they support full dependency injection:

```php
#[AsCommand(name: 'app:send-notifications')]
class SendNotificationsCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
        // MUST call parent constructor
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findActiveUsers();

        foreach ($users as $user) {
            $this->mailer->send($user->getEmail(), 'Notification');
            $this->logger->info('Notification sent', ['user' => $user->getId()]);
        }

        return Command::SUCCESS;
    }
}
```

### Using Parameters and Environment Variables

```php
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:process')]
class ProcessCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,

        #[Autowire('%env(APP_ENV)%')]
        private readonly string $environment,

        #[Autowire(param: 'app.batch_size')]
        private readonly int $batchSize,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Project directory: {$this->projectDir}");
        $output->writeln("Environment: {$this->environment}");
        $output->writeln("Batch size: {$this->batchSize}");

        return Command::SUCCESS;
    }
}
```

---

## Command Structure Methods

### configure() Method

Used to define the command's configuration:

```php
protected function configure(): void
{
    $this
        // Name and description
        ->setName('app:command')
        ->setDescription('Short description')

        // Detailed help
        ->setHelp('Detailed help text...')

        // Aliases
        ->setAliases(['alias1', 'alias2'])

        // Hidden from list
        ->setHidden(false)

        // Arguments
        ->addArgument('arg1', InputArgument::REQUIRED, 'Description')

        // Options
        ->addOption('opt1', 'o', InputOption::VALUE_REQUIRED, 'Description')
    ;
}
```

### initialize() Method

Called before interact() and execute():

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    // Validate input
    $this->validateInput($input);

    // Setup resources
    $this->connection = $this->createDatabaseConnection();

    // Log command start
    $this->logger->info('Command started', [
        'command' => $this->getName(),
        'arguments' => $input->getArguments(),
    ]);
}
```

### interact() Method

Ask questions to complete missing input:

```php
protected function interact(InputInterface $input, OutputInterface $output): void
{
    $io = new SymfonyStyle($input, $output);

    // Only ask if not already provided
    if (!$input->getArgument('filename')) {
        $filename = $io->ask('Enter filename');
        $input->setArgument('filename', $filename);
    }

    // Confirm dangerous operations
    if ($input->getOption('delete')) {
        if (!$io->confirm('This will delete all data. Continue?', false)) {
            throw new \RuntimeException('Operation cancelled');
        }
    }
}
```

### execute() Method

Main command logic - must return an integer exit code:

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    try {
        // Command logic
        $result = $this->processData();

        $io->success('Processed ' . $result . ' items');
        return Command::SUCCESS;  // 0

    } catch (\Exception $e) {
        $io->error($e->getMessage());
        return Command::FAILURE;  // 1
    }
}
```

### Exit Codes

```php
Command::SUCCESS;  // 0 - Success
Command::FAILURE;  // 1 - Generic failure
Command::INVALID;  // 2 - Invalid input

// Custom exit codes
return 0;   // Success
return 1;   // Error
return 2;   // Invalid input
return 130; // Ctrl+C (SIGINT)
return 143; // SIGTERM
```

---

## Arguments in Detail

### Argument Modes

```php
use Symfony\Component\Console\Input\InputArgument;

// Required argument - must be provided
InputArgument::REQUIRED

// Optional argument - can be omitted
InputArgument::OPTIONAL

// Array argument - accepts multiple values (must be last)
InputArgument::IS_ARRAY
```

### Defining Arguments

```php
protected function configure(): void
{
    $this
        // Simple required argument
        ->addArgument('name', InputArgument::REQUIRED)

        // With description
        ->addArgument('name', InputArgument::REQUIRED, 'User name')

        // Optional with default value
        ->addArgument('role', InputArgument::OPTIONAL, 'User role', 'ROLE_USER')

        // Array argument
        ->addArgument('files', InputArgument::IS_ARRAY, 'Files to process')

        // Required array argument
        ->addArgument('files', InputArgument::REQUIRED | InputArgument::IS_ARRAY)
    ;
}
```

### Accessing Arguments

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Get single argument
    $name = $input->getArgument('name');

    // Get array argument
    $files = $input->getArgument('files');  // Returns array

    // Get all arguments
    $allArgs = $input->getArguments();

    // Check if argument exists
    if ($input->hasArgument('optional-arg')) {
        $value = $input->getArgument('optional-arg');
    }

    return Command::SUCCESS;
}
```

### Argument Usage Examples

```bash
# Required argument
php bin/console app:greet John

# Multiple arguments
php bin/console app:user:create john john@example.com

# Optional argument with default
php bin/console app:user:create john           # Uses default role
php bin/console app:user:create john ROLE_ADMIN

# Array argument
php bin/console app:process file1.txt file2.txt file3.txt
```

### Argument Validation

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    $email = $input->getArgument('email');

    // Validate format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \InvalidArgumentException('Invalid email address');
    }

    // Validate file exists
    $filename = $input->getArgument('filename');
    if (!file_exists($filename)) {
        throw new \InvalidArgumentException(sprintf('File "%s" not found', $filename));
    }

    // Validate number range
    $count = (int) $input->getArgument('count');
    if ($count < 1 || $count > 1000) {
        throw new \InvalidArgumentException('Count must be between 1 and 1000');
    }
}
```

---

## Options in Detail

### Option Modes

```php
use Symfony\Component\Console\Input\InputOption;

// No value - boolean flag
InputOption::VALUE_NONE

// Value required
InputOption::VALUE_REQUIRED

// Value optional
InputOption::VALUE_OPTIONAL

// Multiple values
InputOption::VALUE_IS_ARRAY

// Negatable option (--opt or --no-opt)
InputOption::VALUE_NEGATABLE
```

### Defining Options

```php
protected function configure(): void
{
    $this
        // Boolean flag (--verbose or -v)
        ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output')

        // Required value (--format=json)
        ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format')

        // Optional value with default (--limit or --limit=50)
        ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit', 100)

        // Array option
        ->addOption(
            'exclude',
            null,
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Fields to exclude'
        )

        // Negatable option (--colors or --no-colors)
        ->addOption('colors', null, InputOption::VALUE_NEGATABLE, 'Use colors', true)
    ;
}
```

### Accessing Options

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Get boolean option
    $verbose = $input->getOption('verbose');  // true or false

    // Get value option
    $format = $input->getOption('format');

    // Get array option
    $exclude = $input->getOption('exclude');  // Returns array

    // Get all options
    $allOptions = $input->getOptions();

    // Check if option exists
    if ($input->hasOption('custom-option')) {
        $value = $input->getOption('custom-option');
    }

    return Command::SUCCESS;
}
```

### Option Usage Examples

```bash
# Boolean flags
php bin/console app:export --verbose
php bin/console app:export -v

# Options with values
php bin/console app:export --format=json
php bin/console app:export -f json
php bin/console app:export --format json

# Optional value
php bin/console app:export --limit       # Uses default
php bin/console app:export --limit=50    # Uses 50

# Array options
php bin/console app:export --exclude=password --exclude=email
php bin/console app:export --exclude=password --exclude=email --exclude=phone

# Negatable options
php bin/console app:export --colors       # true
php bin/console app:export --no-colors    # false

# Combining options
php bin/console app:export -vf json --limit=100 --exclude=password
```

### Advanced Option Patterns

```php
protected function configure(): void
{
    $this
        // Option with validation callback
        ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Date (YYYY-MM-DD)')

        // Environment-specific option
        ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Environment', 'dev')

        // Multiple format option
        ->addOption(
            'output-format',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output format (json, xml, csv)',
            'json'
        )
    ;
}

protected function initialize(InputInterface $input, OutputInterface $output): void
{
    // Validate date option
    if ($date = $input->getOption('date')) {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('Date must be in YYYY-MM-DD format');
        }
    }

    // Validate format option
    $validFormats = ['json', 'xml', 'csv'];
    $format = $input->getOption('output-format');
    if (!in_array($format, $validFormats)) {
        throw new \InvalidArgumentException(
            sprintf('Format must be one of: %s', implode(', ', $validFormats))
        );
    }
}
```

---

## Input and Output Objects

### InputInterface

The `InputInterface` provides methods to access command input:

```php
interface InputInterface
{
    // Arguments
    public function getArgument(string $name): mixed;
    public function setArgument(string $name, mixed $value): void;
    public function hasArgument(string $name): bool;
    public function getArguments(): array;

    // Options
    public function getOption(string $name): mixed;
    public function setOption(string $name, mixed $value): void;
    public function hasOption(string $name): bool;
    public function getOptions(): array;

    // Interactivity
    public function isInteractive(): bool;
    public function setInteractive(bool $interactive): void;

    // Validation
    public function validate(): void;

    // First argument (command name)
    public function getFirstArgument(): ?string;

    // Parse raw tokens
    public function getParameterOption(string|array $values, mixed $default = false): mixed;
    public function hasParameterOption(string|array $values): bool;
}
```

### OutputInterface

The `OutputInterface` provides methods for writing output:

```php
interface OutputInterface
{
    // Writing output
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void;
    public function writeln(string|iterable $messages, int $options = 0): void;

    // Verbosity
    public function setVerbosity(int $level): void;
    public function getVerbosity(): int;
    public function isQuiet(): bool;
    public function isVerbose(): bool;
    public function isVeryVerbose(): bool;
    public function isDebug(): bool;

    // Decoration (colors/formatting)
    public function setDecorated(bool $decorated): void;
    public function isDecorated(): bool;

    // Formatter
    public function getFormatter(): OutputFormatterInterface;
    public function setFormatter(OutputFormatterInterface $formatter): void;
}
```

### Advanced Input Usage

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Modify input programmatically
    if (!$input->getOption('format')) {
        $input->setOption('format', 'json');
    }

    // Check raw parameter (before parsing)
    if ($input->hasParameterOption(['--dry-run', '-d'])) {
        // Dry run mode
    }

    // Get parameter with default
    $env = $input->getParameterOption('--env', 'dev');

    // Disable interactivity
    $input->setInteractive(false);

    return Command::SUCCESS;
}
```

### Advanced Output Usage

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Write without newline
    $output->write('Processing... ');
    // ... do work ...
    $output->writeln('Done!');

    // Conditional output based on verbosity
    if ($output->isVerbose()) {
        $output->writeln('Detailed information...');
    }

    // Disable colors
    $output->setDecorated(false);

    // Custom formatting
    $formatter = $output->getFormatter();
    $formatter->setStyle('fire', new OutputFormatterStyle('red', 'yellow', ['bold', 'blink']));
    $output->writeln('<fire>Important!</fire>');

    return Command::SUCCESS;
}
```

---

## SymfonyStyle Component

### Overview

`SymfonyStyle` is a high-level output formatter that provides a consistent, beautiful command-line interface.

```php
use Symfony\Component\Console\Style\SymfonyStyle;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    // Now use $io instead of $output

    return Command::SUCCESS;
}
```

### Text Output Methods

```php
// Title (large, underlined)
$io->title('Application Setup');

// Section (medium, underlined)
$io->section('Configuration');

// Regular text
$io->text('This is regular text');
$io->text([
    'Line 1',
    'Line 2',
    'Line 3',
]);

// Listing
$io->listing([
    'Item 1',
    'Item 2',
    'Item 3',
]);

// Newlines
$io->newLine();
$io->newLine(3);
```

### Status Messages

```php
// Success message (green)
$io->success('Operation completed successfully!');
$io->success([
    'Multiple lines',
    'of success messages',
]);

// Warning message (yellow)
$io->warning('This is a warning');

// Error message (red)
$io->error('An error occurred');
$io->error([
    'Multiple errors:',
    '- Error 1',
    '- Error 2',
]);

// Note message (blue)
$io->note('This is a note');

// Caution message (red)
$io->caution('Be very careful!');

// Info message
$io->info('Informational message');
```

### Tables

```php
// Simple table
$io->table(
    ['ID', 'Name', 'Email'],
    [
        [1, 'John', 'john@example.com'],
        [2, 'Jane', 'jane@example.com'],
    ]
);

// Horizontal table
$io->horizontalTable(
    ['Name', 'Email'],
    [
        ['John', 'john@example.com'],
        ['Jane', 'jane@example.com'],
    ]
);

// Definition list
$io->definitionList(
    'User Information',
    ['Name' => 'John Doe'],
    ['Email' => 'john@example.com'],
    ['Role' => 'Admin'],
);
```

### Questions

```php
// Simple ask
$name = $io->ask('What is your name?');
$name = $io->ask('What is your name?', 'John'); // with default

// Ask with validation
$age = $io->ask('What is your age?', null, function ($answer) {
    if (!is_numeric($answer)) {
        throw new \RuntimeException('Age must be a number');
    }
    return (int) $answer;
});

// Hidden input
$password = $io->askHidden('Password');

// Confirmation
if ($io->confirm('Continue?', false)) {
    // User said yes
}

// Choice
$color = $io->choice(
    'Favorite color',
    ['Red', 'Blue', 'Green'],
    'Blue'
);

// Multiple choice is not directly supported, use QuestionHelper
```

### Progress Indicators

```php
// Simple progress bar
$io->progressStart(100);
for ($i = 0; $i < 100; $i++) {
    // Do work
    $io->progressAdvance();
}
$io->progressFinish();

// Progress bar with custom advancement
$io->progressStart();
$io->progressAdvance(10);  // Advance by 10
$io->progressAdvance(25);  // Advance by 25
$io->progressFinish();
```

### Writing Output

```php
// Direct write (for advanced use)
$io->write('Text without newline');
$io->writeln('Text with newline');

// Styled write
$io->writeln('<info>Colored text</info>');
```

---

## Progress Bars

### Basic Progress Bar

```php
use Symfony\Component\Console\Helper\ProgressBar;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $items = range(1, 100);

    $progressBar = new ProgressBar($output, count($items));
    $progressBar->start();

    foreach ($items as $item) {
        // Process item
        usleep(50000);

        $progressBar->advance();
    }

    $progressBar->finish();
    $output->writeln(''); // New line

    return Command::SUCCESS;
}
```

### Progress Bar Formats

```php
$progressBar = new ProgressBar($output, 100);

// Built-in formats
$progressBar->setFormat('normal');
$progressBar->setFormat('verbose');
$progressBar->setFormat('very_verbose');
$progressBar->setFormat('debug');

// normal:     [==============>-------------]  50%
// verbose:    [==============>-------------]  50/100  50%
// very_verbose: [==============>-------------]  50/100  50%  1 sec
// debug:      [==============>-------------]  50/100  50%  1 sec/2 secs  16.0 MiB
```

### Custom Progress Bar Format

```php
$progressBar = new ProgressBar($output, 100);

// Custom format
$progressBar->setFormat(
    ' %current%/%max% [%bar%] %percent:3s%%'
    . ' %elapsed:6s%/%estimated:-6s%'
    . ' %memory:6s%'
);

// Available placeholders:
// %current%    - Current step
// %max%        - Maximum steps
// %bar%        - The bar itself
// %percent%    - Percentage
// %elapsed%    - Elapsed time
// %estimated%  - Estimated remaining time
// %remaining%  - Remaining steps
// %memory%     - Current memory usage
```

### Progress Bar Customization

```php
$progressBar = new ProgressBar($output, 100);

// Customize appearance
$progressBar->setBarCharacter('<fg=green>=</>');
$progressBar->setEmptyBarCharacter('-');
$progressBar->setProgressCharacter('>');
$progressBar->setBarWidth(50);

// Set redraw frequency
$progressBar->setRedrawFrequency(10);  // Redraw every 10 steps
$progressBar->minSecondsBetweenRedraws(0.1);  // Or every 0.1 seconds

// Override steps
$progressBar->setMaxSteps(200);

$progressBar->start();
```

### Custom Progress Messages

```php
$progressBar = new ProgressBar($output, count($files));

// Define custom placeholder
$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
$progressBar->setMessage('Starting...', 'message');

$progressBar->start();

foreach ($files as $file) {
    $progressBar->setMessage('Processing: ' . $file, 'message');
    processFile($file);
    $progressBar->advance();
}

$progressBar->setMessage('Complete!', 'message');
$progressBar->finish();
```

### Progress Bar without Max Steps

```php
// Indeterminate progress
$progressBar = new ProgressBar($output);
$progressBar->start();

while ($hasMoreWork) {
    doWork();
    $progressBar->advance();
}

$progressBar->finish();
```

---

## Tables

### Basic Table

```php
use Symfony\Component\Console\Helper\Table;

$table = new Table($output);
$table
    ->setHeaders(['ID', 'Name', 'Email'])
    ->setRows([
        [1, 'John', 'john@example.com'],
        [2, 'Jane', 'jane@example.com'],
        [3, 'Bob', 'bob@example.com'],
    ])
;
$table->render();
```

### Table Styles

```php
$table = new Table($output);

// Available styles
$table->setStyle('default');    // Default style
$table->setStyle('borderless'); // No borders
$table->setStyle('compact');    // Compact spacing
$table->setStyle('box');        // Box drawing characters
$table->setStyle('box-double'); // Double-line box

$table
    ->setHeaders(['Name', 'Email'])
    ->setRows([...])
;
$table->render();
```

### Advanced Table Features

```php
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

$table = new Table($output);

$table->setHeaders([
    // Header with colspan
    [new TableCell('User Information', ['colspan' => 3])],
    ['ID', 'Name', 'Email'],
]);

$table->setRows([
    [1, 'John', 'john@example.com'],
    new TableSeparator(),  // Horizontal line
    [2, 'Jane', 'jane@example.com'],
    new TableSeparator(),
    // Cell with colspan
    [new TableCell('Total: 2 users', ['colspan' => 3])],
]);

$table->render();
```

### Dynamic Table Building

```php
$table = new Table($output);
$table->setHeaders(['Name', 'Email', 'Role']);

// Add rows dynamically
foreach ($users as $user) {
    $table->addRow([
        $user->getName(),
        $user->getEmail(),
        $user->getRole(),
    ]);
}

$table->render();
```

### Column Width and Alignment

```php
$table = new Table($output);

// Set column widths
$table->setColumnWidths([10, 30, 30]);

// Set column max widths
$table->setColumnMaxWidths([10, 30, 30]);

// Column content will wrap if too long

$table
    ->setHeaders(['ID', 'Name', 'Description'])
    ->setRows([...])
;
$table->render();
```

---

## Question Helper

### Basic Questions

```php
use Symfony\Component\Console\Question\Question;

$helper = $this->getHelper('question');

$question = new Question('Please enter your name: ', 'John');
$name = $helper->ask($input, $output, $question);
```

### Hidden Questions (Passwords)

```php
use Symfony\Component\Console\Question\Question;

$question = new Question('Password: ');
$question->setHidden(true);
$question->setHiddenFallback(false);

$password = $helper->ask($input, $output, $question);
```

### Choice Questions

```php
use Symfony\Component\Console\Question\ChoiceQuestion;

$question = new ChoiceQuestion(
    'Please select your role',
    ['Admin', 'Editor', 'User'],
    0  // default
);

// Single choice
$role = $helper->ask($input, $output, $question);

// Multiple choice
$question->setMultiselect(true);
$roles = $helper->ask($input, $output, $question);
```

### Confirmation Questions

```php
use Symfony\Component\Console\Question\ConfirmationQuestion;

$question = new ConfirmationQuestion(
    'Continue with this action? [y/N] ',
    false  // default to No
);

if ($helper->ask($input, $output, $question)) {
    // User confirmed
}
```

### Question Validation

```php
$question = new Question('Email: ');

$question->setValidator(function ($answer) {
    if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException('Invalid email address');
    }
    return $answer;
});

// Normalizer (applied before validation)
$question->setNormalizer(function ($answer) {
    return strtolower(trim($answer));
});

// Maximum attempts
$question->setMaxAttempts(3);

$email = $helper->ask($input, $output, $question);
```

### Autocomplete

```php
$question = new Question('Country: ');

$question->setAutocompleterValues([
    'United States',
    'United Kingdom',
    'Germany',
    'France',
    'Spain',
    'Italy',
]);

// Or use a callback
$question->setAutocompleterCallback(function ($input) {
    // Return array of suggestions based on $input
    return array_filter($this->getAllCountries(), function($country) use ($input) {
        return str_starts_with(strtolower($country), strtolower($input));
    });
});

$country = $helper->ask($input, $output, $question);
```

---

## Console Events

### Event Types

```php
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
```

### ConsoleCommandEvent

Dispatched before command execution:

```php
namespace App\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        $this->logger->info('Command started', [
            'name' => $command->getName(),
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions(),
        ]);

        // Disable command
        // $event->disableCommand();

        // Set a different command
        // $event->setCommand($anotherCommand);
    }
}
```

### ConsoleTerminateEvent

Dispatched after command execution:

```php
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

public static function getSubscribedEvents(): array
{
    return [
        ConsoleTerminateEvent::class => 'onConsoleTerminate',
    ];
}

public function onConsoleTerminate(ConsoleTerminateEvent $event): void
{
    $command = $event->getCommand();
    $exitCode = $event->getExitCode();
    $input = $event->getInput();
    $output = $event->getOutput();

    $this->logger->info('Command finished', [
        'name' => $command->getName(),
        'exit_code' => $exitCode,
        'duration' => time() - $_SERVER['REQUEST_TIME'],
    ]);

    // Modify exit code
    if ($exitCode > 0) {
        $event->setExitCode(1);  // Normalize all errors to 1
    }
}
```

### ConsoleErrorEvent

Dispatched when a command throws an exception:

```php
use Symfony\Component\Console\Event\ConsoleErrorEvent;

public static function getSubscribedEvents(): array
{
    return [
        ConsoleErrorEvent::class => 'onConsoleError',
    ];
}

public function onConsoleError(ConsoleErrorEvent $event): void
{
    $command = $event->getCommand();
    $error = $event->getError();
    $output = $event->getOutput();

    $this->logger->error('Command error', [
        'command' => $command->getName(),
        'message' => $error->getMessage(),
        'trace' => $error->getTraceAsString(),
    ]);

    // Send error notification
    $this->notifier->send('Command failed: ' . $error->getMessage());

    // Change exit code
    $event->setExitCode(99);

    // Or replace the exception
    // $event->setError(new \Exception('Custom error'));
}
```

### ConsoleSignalEvent

Handle OS signals (SIGINT, SIGTERM, etc.):

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;

#[AsCommand(name: 'app:daemon')]
class DaemonCommand extends Command implements SignalableCommandInterface
{
    private bool $shouldStop = false;

    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM, SIGUSR1];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->output->writeln('Received signal: ' . $signal);

        if ($signal === SIGINT || $signal === SIGTERM) {
            $this->output->writeln('Shutting down gracefully...');
            $this->shouldStop = true;
            return Command::SUCCESS;
        }

        if ($signal === SIGUSR1) {
            $this->output->writeln('Reloading configuration...');
            $this->reloadConfig();
            return false; // Continue execution
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        while (!$this->shouldStop) {
            $this->doWork();
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
```

---

## Verbosity Levels

### Levels

```php
use Symfony\Component\Console\Output\OutputInterface;

OutputInterface::VERBOSITY_QUIET;         // -q, --quiet
OutputInterface::VERBOSITY_NORMAL;        // (default)
OutputInterface::VERBOSITY_VERBOSE;       // -v
OutputInterface::VERBOSITY_VERY_VERBOSE;  // -vv
OutputInterface::VERBOSITY_DEBUG;         // -vvv
```

### Checking Verbosity

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Check level
    if ($output->isQuiet()) {
        // Suppress all output
        return Command::SUCCESS;
    }

    if ($output->isVerbose()) {
        $output->writeln('Verbose mode enabled');
    }

    if ($output->isVeryVerbose()) {
        $output->writeln('Very verbose mode enabled');
    }

    if ($output->isDebug()) {
        $output->writeln('Debug mode enabled');
        $output->writeln('Memory: ' . memory_get_usage());
    }

    // Get numeric level
    $level = $output->getVerbosity();

    return Command::SUCCESS;
}
```

### Verbosity-Specific Output

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Always shown (except in quiet mode)
    $output->writeln('Processing...');

    // Only in verbose mode (-v)
    $output->writeln('Detailed info...', OutputInterface::VERBOSITY_VERBOSE);

    // Only in very verbose mode (-vv)
    $output->writeln('More details...', OutputInterface::VERBOSITY_VERY_VERBOSE);

    // Only in debug mode (-vvv)
    $output->writeln('Debug info...', OutputInterface::VERBOSITY_DEBUG);

    return Command::SUCCESS;
}
```

### Setting Verbosity

```php
// Set programmatically
$output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);

// From input
if ($input->getOption('verbose')) {
    $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
}
```

---

## Running Commands Programmatically

### Using Application::find()

```php
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SomeService
{
    public function __construct(
        private readonly Application $application,
    ) {}

    public function importData(): void
    {
        $command = $this->application->find('app:import');

        $input = new ArrayInput([
            'filename' => 'data.csv',
            '--format' => 'csv',
            '--dry-run' => true,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Import failed');
        }

        $content = $output->fetch();
    }
}
```

### Using Application::doRun()

```php
public function runCommand(): void
{
    $input = new ArrayInput([
        'command' => 'app:import',
        'filename' => 'data.csv',
    ]);

    // Disable interactivity
    $input->setInteractive(false);

    $output = new BufferedOutput();

    $this->application->setAutoExit(false);
    $exitCode = $this->application->doRun($input, $output);

    $content = $output->fetch();
}
```

### Chaining Commands

```php
#[AsCommand(name: 'app:deploy')]
class DeployCommand extends Command
{
    public function __construct(
        private readonly Application $application,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $commands = [
            ['command' => 'cache:clear'],
            ['command' => 'doctrine:migrations:migrate', '--no-interaction' => true],
            ['command' => 'assets:install', 'target' => 'public'],
        ];

        foreach ($commands as $commandData) {
            $commandName = $commandData['command'];
            unset($commandData['command']);

            $io->section("Running: $commandName");

            $command = $this->application->find($commandName);
            $returnCode = $command->run(new ArrayInput($commandData), $output);

            if ($returnCode !== Command::SUCCESS) {
                $io->error("Command $commandName failed!");
                return Command::FAILURE;
            }
        }

        $io->success('Deployment complete!');

        return Command::SUCCESS;
    }
}
```

---

## Testing Commands

### Basic Command Test

```php
namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MyCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:my-command');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'argument' => 'value',
            '--option' => 'value',
        ]);

        // Assert exit code
        $this->assertSame(0, $commandTester->getStatusCode());

        // Assert output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('expected text', $output);
    }
}
```

### Testing Interactive Commands

```php
public function testInteractiveCommand(): void
{
    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $command = $application->find('app:user:create');
    $commandTester = new CommandTester($command);

    // Provide inputs for questions
    $commandTester->setInputs([
        'john',              // username
        'john@example.com',  // email
        'y',                 // confirmation
    ]);

    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('User created', $output);
}
```

### Testing Command Output Streams

```php
public function testCommandStreams(): void
{
    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $command = $application->find('app:process');
    $commandTester = new CommandTester($command);

    $commandTester->execute([], [
        'capture_stderr_separately' => true,
    ]);

    $stdout = $commandTester->getDisplay();
    $stderr = $commandTester->getErrorOutput();

    $this->assertStringContainsString('Success', $stdout);
    $this->assertEmpty($stderr);
}
```

### Testing with Different Verbosity

```php
public function testVerboseOutput(): void
{
    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $command = $application->find('app:my-command');
    $commandTester = new CommandTester($command);

    $commandTester->execute([], [
        'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
    ]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('verbose output', $output);
}
```

### Testing Command with Services

```php
public function testCommandWithMockedService(): void
{
    // Create mock
    $mailer = $this->createMock(MailerInterface::class);
    $mailer->expects($this->once())
        ->method('send')
        ->with('test@example.com', 'Subject');

    // Replace service in container
    self::getContainer()->set(MailerInterface::class, $mailer);

    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $command = $application->find('app:send-email');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'email' => 'test@example.com',
    ]);

    $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
}
```

### Testing Command Exit Codes

```php
public function testCommandFailure(): void
{
    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $command = $application->find('app:risky-operation');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        'filename' => 'nonexistent.txt',
    ]);

    // Expect failure
    $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('File not found', $output);
}
```

---

## Best Practices

### 1. Use Type Hints and Return Types

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Always return int
    return Command::SUCCESS;
}
```

### 2. Validate Early

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    // Validate all input before execution
    $this->validateInput($input);
}
```

### 3. Use SymfonyStyle

```php
// Consistent, beautiful output
$io = new SymfonyStyle($input, $output);
$io->success('Done!');
```

### 4. Handle Errors Gracefully

```php
try {
    // Command logic
    return Command::SUCCESS;
} catch (\Exception $e) {
    $io->error($e->getMessage());
    return Command::FAILURE;
}
```

### 5. Make Commands Testable

```php
// Use dependency injection
public function __construct(
    private readonly ServiceInterface $service,
) {
    parent::__construct();
}
```

### 6. Document Your Commands

```php
#[AsCommand(
    name: 'app:command',
    description: 'Clear, concise description'
)]
class MyCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('Detailed help text...');
    }
}
```

### 7. Use Appropriate Verbosity

```php
// Normal output - always shown
$io->writeln('Processing...');

// Verbose output - only with -v
if ($output->isVerbose()) {
    $io->writeln('Detailed information');
}
```

### 8. Return Correct Exit Codes

```php
return Command::SUCCESS;  // 0
return Command::FAILURE;  // 1
return Command::INVALID;  // 2
```
