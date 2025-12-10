# Chapter 11: Console - CLI Application

This chapter demonstrates how to build a CLI application framework similar to Symfony Console.

## Table of Contents

1. [Introduction to CLI Applications](#introduction-to-cli-applications)
2. [Command Structure](#command-structure)
3. [Input/Output Handling](#inputoutput-handling)
4. [Helpers (Progress Bar, Table, Question)](#helpers)
5. [How Symfony Console Works](#how-symfony-console-works)
6. [Examples](#examples)
7. [Testing Commands](#testing-commands)

---

## Introduction to CLI Applications

CLI (Command Line Interface) applications are essential for:
- Running background tasks
- Database migrations
- Cache clearing
- Cron jobs
- Developer tools
- Automation scripts

Symfony Console provides a robust framework for building CLI applications with:
- Command registration and discovery
- Argument and option parsing
- Formatted output (colors, tables, progress bars)
- Interactive input (questions, confirmations)
- Auto-completion support

## Command Structure

A typical Symfony command has three main parts:

### 1. Configuration
Define the command name, description, arguments, and options:

```php
#[AsCommand(
    name: 'app:greet',
    description: 'Greets a user',
)]
class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Who do you want to greet?')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Uppercase the output');
    }
}
```

### 2. Execution
Implement the business logic:

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $name = $input->getArgument('name');
    $message = "Hello, {$name}!";

    if ($input->getOption('uppercase')) {
        $message = strtoupper($message);
    }

    $output->writeln($message);

    return Command::SUCCESS;
}
```

### 3. Return Codes
Commands should return appropriate exit codes:
- `Command::SUCCESS` (0) - Success
- `Command::FAILURE` (1) - Generic failure
- `Command::INVALID` (2) - Invalid usage

## Input/Output Handling

### Input Interface

The `InputInterface` provides access to arguments and options:

```php
// Arguments (positional parameters)
$input->getArgument('name');
$input->hasArgument('name');

// Options (named parameters with -- prefix)
$input->getOption('force');
$input->hasOption('force');

// Interactive mode
$input->isInteractive();
```

### Argument Types

```php
// Required argument
addArgument('name', InputArgument::REQUIRED, 'User name')

// Optional argument
addArgument('last-name', InputArgument::OPTIONAL, 'Last name')

// Array argument (must be last)
addArgument('names', InputArgument::IS_ARRAY, 'Multiple names')

// With default value
addArgument('environment', InputArgument::OPTIONAL, 'Environment', 'dev')
```

### Option Types

```php
// Flag option (no value)
addOption('force', 'f', InputOption::VALUE_NONE, 'Force execution')

// Required value option
addOption('env', null, InputOption::VALUE_REQUIRED, 'Environment')

// Optional value option
addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Number of iterations', 1)

// Array option (can be specified multiple times)
addOption('exclude', null, InputOption::VALUE_IS_ARRAY, 'Exclude patterns')
```

### Output Interface

The `OutputInterface` handles all output:

```php
// Basic output
$output->write('Hello');      // Without newline
$output->writeln('World');    // With newline

// Verbosity levels
$output->writeln('Always visible');
$output->writeln('Verbose', OutputInterface::VERBOSITY_VERBOSE);
$output->writeln('Very verbose', OutputInterface::VERBOSITY_VERY_VERBOSE);
$output->writeln('Debug', OutputInterface::VERBOSITY_DEBUG);

// Formatted output
$output->writeln('<info>Info message</info>');
$output->writeln('<comment>Comment message</comment>');
$output->writeln('<question>Question message</question>');
$output->writeln('<error>Error message</error>');
```

### Output Formatting

```php
// Colors and styles
<fg=red>Red text</>
<fg=green>Green text</>
<bg=blue>Blue background</>
<fg=black;bg=cyan>Black on cyan</>

// Text styles
<bold>Bold text</>
<underline>Underlined text</>
<blink>Blinking text</>

// Combinations
<fg=white;bg=red;bold>Bold white on red</>
```

## Helpers

Symfony Console provides several helpers to enhance CLI user experience:

### ProgressBar

For long-running operations:

```php
use Console\Helper\ProgressBar;

$progressBar = new ProgressBar($output, $max);
$progressBar->start();

foreach ($items as $item) {
    // Process item
    $progressBar->advance();
}

$progressBar->finish();
```

### Table

For displaying tabular data:

```php
use Console\Helper\Table;

$table = new Table($output);
$table
    ->setHeaders(['ID', 'Name', 'Email'])
    ->setRows([
        [1, 'John Doe', 'john@example.com'],
        [2, 'Jane Smith', 'jane@example.com'],
    ]);
$table->render();
```

### SymfonyStyle

A high-level output formatter:

```php
use Console\Style\SymfonyStyle;

$io = new SymfonyStyle($input, $output);

// Titles and sections
$io->title('Main Title');
$io->section('Section Title');

// Messages
$io->success('Operation successful!');
$io->error('An error occurred!');
$io->warning('Warning message');
$io->note('Note message');

// Lists
$io->listing(['Item 1', 'Item 2', 'Item 3']);

// Tables
$io->table(
    ['Header 1', 'Header 2'],
    [['Value 1', 'Value 2']]
);

// Progress bar
$io->progressStart(100);
foreach ($items as $item) {
    // Process
    $io->progressAdvance();
}
$io->progressFinish();

// Questions
$answer = $io->ask('What is your name?');
$password = $io->askHidden('Enter password');
$confirm = $io->confirm('Continue?', true);
$choice = $io->choice('Select option', ['opt1', 'opt2'], 'opt1');
```

## How Symfony Console Works

### 1. Application Lifecycle

```
bin/console command:name --option argument
     |
     v
Application::run()
     |
     v
Parse input (ArgvInput)
     |
     v
Find command by name
     |
     v
Command::run()
     |
     v
Command::initialize() (optional)
     |
     v
Command::interact() (optional, for interactive input)
     |
     v
Command::execute() (required)
     |
     v
Return exit code
```

### 2. Application Class

The Application class:
- Registers all available commands
- Parses command-line arguments
- Finds and executes the requested command
- Handles errors and exceptions
- Provides a unified entry point

```php
$application = new Application('My App', '1.0.0');

// Register commands
$application->add(new GreetCommand());
$application->add(new ListUsersCommand());

// Run the application
$application->run();
```

### 3. Input Parsing

The `ArgvInput` class parses `$_SERVER['argv']`:

```
php bin/console app:greet John --uppercase -vvv
                   |      |         |        |
                 command  arg     option   flags
```

Parsed into:
- Command name: `app:greet`
- Arguments: `['name' => 'John']`
- Options: `['uppercase' => true, 'verbose' => 3]`

### 4. Command Discovery

Commands can be registered:

**Manually:**
```php
$application->add(new MyCommand());
```

**Via Attribute:**
```php
#[AsCommand(
    name: 'app:my-command',
    description: 'My command description'
)]
class MyCommand extends Command { }
```

### 5. Dependency Injection

Commands can receive dependencies via constructor:

```php
class ListUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findAll();
        // ...
    }
}
```

## Examples

### Example 1: Simple Greet Command

```bash
php bin/console app:greet John
# Output: Hello, John!

php bin/console app:greet John --uppercase
# Output: HELLO, JOHN!

php bin/console app:greet John --yell
# Output: HELLO, JOHN!!!
```

### Example 2: List Users Command

```bash
php bin/console app:list-users
# Output:
# +----+------------+--------------------+
# | ID | Name       | Email              |
# +----+------------+--------------------+
# | 1  | John Doe   | john@example.com   |
# | 2  | Jane Smith | jane@example.com   |
# +----+------------+--------------------+

php bin/console app:list-users --format=json
# Output: [{"id":1,"name":"John Doe","email":"john@example.com"},...]
```

## Testing Commands

### Unit Testing Commands

```php
use PHPUnit\Framework\TestCase;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;

class GreetCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'John',
            '--uppercase' => true,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('HELLO, JOHN', $output->fetch());
    }
}
```

### Testing with CommandTester

```php
use Console\Tester\CommandTester;

class GreetCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new GreetCommand();
        $tester = new CommandTester($command);

        $tester->execute([
            'name' => 'John',
            '--uppercase' => true,
        ]);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('HELLO, JOHN', $tester->getDisplay());
    }
}
```

## Best Practices

1. **Single Responsibility**: Each command should do one thing well
2. **Meaningful Names**: Use verb-noun pattern (e.g., `cache:clear`, `user:create`)
3. **Proper Exit Codes**: Always return appropriate exit codes
4. **Validation**: Validate input before processing
5. **Error Handling**: Catch and handle exceptions gracefully
6. **Progress Indication**: Use progress bars for long operations
7. **Confirmation**: Ask for confirmation on destructive operations
8. **Verbosity**: Support different verbosity levels
9. **Help Text**: Provide clear descriptions and help text
10. **Idempotency**: Commands should be safe to run multiple times

## Advanced Features

### Command Aliases

```php
protected function configure(): void
{
    $this
        ->setName('app:greet')
        ->setAliases(['greet', 'hello']);
}
```

### Hidden Commands

```php
#[AsCommand(
    name: 'app:internal',
    hidden: true
)]
```

### Command Lifecycle Hooks

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    // Called before interact() and execute()
    // Good for setup
}

protected function interact(InputInterface $input, OutputInterface $output): void
{
    // Called after initialize() and before execute()
    // Good for asking questions if arguments are missing
}

protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Main command logic
}
```

### Event Listeners

```php
// Listen to console events
ConsoleEvents::COMMAND     // Before command execution
ConsoleEvents::TERMINATE   // After command execution
ConsoleEvents::ERROR       // When command throws exception
```

## Key Concepts Summary

1. **Application**: Main entry point, manages command registration and execution
2. **Command**: Defines command logic, arguments, and options
3. **Input**: Parses and provides access to command-line arguments
4. **Output**: Handles all output to the console
5. **Helpers**: Utilities for progress bars, tables, questions
6. **SymfonyStyle**: High-level API for beautiful console output
7. **Exit Codes**: Indicate success or failure to the shell

## Running the Examples

```bash
# Make the console executable
chmod +x bin/console

# Run commands
./bin/console app:greet World
./bin/console app:greet World --uppercase
./bin/console app:greet World --yell
./bin/console app:list-users
./bin/console app:list-users --format=json

# View help
./bin/console list
./bin/console help app:greet
```

## Further Reading

- Symfony Console Component Documentation
- Command Design Pattern
- CLI Best Practices
- Shell Scripting Integration
- Process Management in PHP
