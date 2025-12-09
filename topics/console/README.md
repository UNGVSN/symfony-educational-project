# Symfony Console Component

Master building powerful command-line applications with Symfony's Console component.

---

## Learning Objectives

After completing this topic, you will be able to:

- Create custom console commands using modern PHP 8.2+ attributes
- Configure command arguments and options effectively
- Use input and output objects for interactive CLI applications
- Format output with SymfonyStyle for professional-looking commands
- Implement progress bars, tables, and other UI helpers
- Handle console events for advanced command behavior
- Test console commands comprehensively
- Run commands programmatically from your application
- Manage verbosity levels and error handling
- Build interactive commands with question helpers

---

## Prerequisites

- PHP 8.2+ fundamentals
- Symfony basics (dependency injection, services)
- Basic command-line interface knowledge
- Understanding of OOP concepts

---

## Topics Covered

1. [Console Component Overview](#1-console-component-overview)
2. [Creating Commands](#2-creating-commands)
3. [Command Configuration](#3-command-configuration)
4. [Arguments and Options](#4-arguments-and-options)
5. [Input and Output](#5-input-and-output)
6. [SymfonyStyle](#6-symfonystyle)
7. [UI Helpers](#7-ui-helpers)
8. [Interactive Commands](#8-interactive-commands)
9. [Console Events](#9-console-events)
10. [Verbosity Levels](#10-verbosity-levels)
11. [Running Commands Programmatically](#11-running-commands-programmatically)
12. [Testing Commands](#12-testing-commands)

---

## 1. Console Component Overview

### What is the Console Component?

The Console component allows you to create command-line applications with:
- Standardized command structure
- Rich input/output formatting
- Built-in help system
- Event system for hooks
- Testing capabilities

### Basic Command Structure

```bash
# Running commands
php bin/console command:name [arguments] [options]

# List all commands
php bin/console list

# Get help for a command
php bin/console help command:name
php bin/console command:name --help
```

### Command Lifecycle

```
1. Input Binding - Parse arguments/options
2. Input Validation - Validate required inputs
3. Initialization - Execute initialize() method
4. Interaction - Execute interact() method
5. Execution - Execute execute() method
6. Termination - Cleanup and return exit code
```

---

## 2. Creating Commands

### Modern Attribute-Based Command

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:greet',
    description: 'Greets a user',
    aliases: ['greet'],
    hidden: false
)]
class GreetCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello from Symfony Console!');

        return Command::SUCCESS;
    }
}
```

### Command with Dependency Injection

```php
#[AsCommand(
    name: 'app:send-email',
    description: 'Send email notifications'
)]
class SendEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $this->mailer->send($user->getEmail(), 'Notification');
        }

        $output->writeln(sprintf('Sent %d emails', count($users)));

        return Command::SUCCESS;
    }
}
```

### Return Codes

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    try {
        // Your logic here
        return Command::SUCCESS;  // 0
    } catch (\Exception $e) {
        $output->writeln('<error>' . $e->getMessage() . '</error>');
        return Command::FAILURE;  // 1
    }

    // Other return codes
    return Command::INVALID;  // 2
}
```

---

## 3. Command Configuration

### Using the #[AsCommand] Attribute

```php
#[AsCommand(
    name: 'app:process-data',
    description: 'Process data from various sources',
    aliases: ['process', 'data:process'],
    hidden: false  // Hide from command list if true
)]
class ProcessDataCommand extends Command
{
    // Command implementation
}
```

### Legacy Configure Method (Still Supported)

```php
class ProcessDataCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:process-data')
            ->setDescription('Process data from various sources')
            ->setAliases(['process', 'data:process'])
            ->setHelp('This command processes data...')
        ;
    }
}
```

### Combining Attribute and Configure

```php
#[AsCommand(name: 'app:import')]
class ImportCommand extends Command
{
    protected function configure(): void
    {
        // Additional configuration
        $this->setHelp(<<<'EOT'
The <info>%command.name%</info> command imports data:

    <info>php %command.full_name% --source=csv</info>

For more information, see the documentation.
EOT
        );
    }
}
```

---

## 4. Arguments and Options

### Defining Arguments

```php
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:user:create')]
class CreateUserCommand extends Command
{
    protected function configure(): void
    {
        $this
            // Required argument
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'The username of the user'
            )
            // Optional argument
            ->addArgument(
                'email',
                InputArgument::OPTIONAL,
                'The email of the user'
            )
            // Optional with default value
            ->addArgument(
                'role',
                InputArgument::OPTIONAL,
                'The role of the user',
                'ROLE_USER'
            )
            // Array argument (must be last)
            ->addArgument(
                'permissions',
                InputArgument::IS_ARRAY,
                'User permissions (separate multiple with space)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');
        $permissions = $input->getArgument('permissions');

        // Create user logic...

        return Command::SUCCESS;
    }
}
```

### Argument Modes

```php
InputArgument::REQUIRED      // Must be provided
InputArgument::OPTIONAL      // Can be omitted
InputArgument::IS_ARRAY      // Multiple values allowed (must be last)
```

### Defining Options

```php
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'app:export')]
class ExportCommand extends Command
{
    protected function configure(): void
    {
        $this
            // Option without value (flag)
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute the command in dry-run mode'
            )
            // Option with required value
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Export format (csv, json, xml)'
            )
            // Option with optional value
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Limit number of records',
                100  // default value
            )
            // Array option
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Fields to exclude (multiple values allowed)'
            )
            // Negatable option (Symfony 6.2+)
            ->addOption(
                'colors',
                null,
                InputOption::VALUE_NEGATABLE,
                'Enable/disable colors',
                true  // default
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run');
        $format = $input->getOption('format');
        $limit = $input->getOption('limit');
        $exclude = $input->getOption('exclude');
        $colors = $input->getOption('colors');

        if ($isDryRun) {
            $output->writeln('Running in dry-run mode...');
        }

        // Export logic...

        return Command::SUCCESS;
    }
}
```

### Option Modes

```php
InputOption::VALUE_NONE         // No value (boolean flag)
InputOption::VALUE_REQUIRED     // Value must be provided
InputOption::VALUE_OPTIONAL     // Value can be omitted
InputOption::VALUE_IS_ARRAY     // Multiple values allowed
InputOption::VALUE_NEGATABLE    // Can use --no-option to negate
```

### Usage Examples

```bash
# Arguments
php bin/console app:user:create john
php bin/console app:user:create john john@example.com ROLE_ADMIN
php bin/console app:user:create john john@example.com ROLE_USER read write delete

# Options
php bin/console app:export --dry-run
php bin/console app:export --format=json
php bin/console app:export -f json
php bin/console app:export --limit=50
php bin/console app:export --exclude=password --exclude=email
php bin/console app:export --colors
php bin/console app:export --no-colors
```

---

## 5. Input and Output

### InputInterface Methods

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Get arguments
    $username = $input->getArgument('username');
    $all = $input->getArguments();  // All arguments as array

    // Get options
    $format = $input->getOption('format');
    $all = $input->getOptions();  // All options as array

    // Check if argument/option exists
    $hasEmail = $input->hasArgument('email');
    $hasFormat = $input->hasOption('format');

    // Check interactivity
    if ($input->isInteractive()) {
        // Can ask questions
    }

    // Validate input
    $input->validate();

    return Command::SUCCESS;
}
```

### OutputInterface Methods

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Basic output
    $output->write('Message without newline');
    $output->writeln('Message with newline');

    // Multiple lines
    $output->writeln([
        'Line 1',
        'Line 2',
        'Line 3',
    ]);

    // Styled output
    $output->writeln('<info>Informational message</info>');
    $output->writeln('<comment>Comment message</comment>');
    $output->writeln('<question>Question message</question>');
    $output->writeln('<error>Error message</error>');

    // Custom styles
    $output->writeln('<fg=green>Green text</>');
    $output->writeln('<bg=yellow;fg=black>Black on yellow</>');
    $output->writeln('<options=bold>Bold text</>');
    $output->writeln('<options=underscore>Underlined</>');

    // Verbosity-aware output
    if ($output->isVerbose()) {
        $output->writeln('Verbose output');
    }

    return Command::SUCCESS;
}
```

### Output Formatting Tags

```php
// Predefined styles
<info>text</info>        // Green
<comment>text</comment>  // Yellow
<question>text</question> // Black on cyan
<error>text</error>      // White on red

// Foreground colors
<fg=black>text</>
<fg=red>text</>
<fg=green>text</>
<fg=yellow>text</>
<fg=blue>text</>
<fg=magenta>text</>
<fg=cyan>text</>
<fg=white>text</>

// Background colors
<bg=black>text</>
<bg=red>text</>
// ... etc

// Options
<options=bold>text</>
<options=underscore>text</>
<options=blink>text</>
<options=reverse>text</>
<options=conceal>text</>

// Combine styles
<fg=white;bg=blue;options=bold>text</>
```

---

## 6. SymfonyStyle

### Using SymfonyStyle

```php
use Symfony\Component\Console\Style\SymfonyStyle;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    // Title
    $io->title('User Management Command');

    // Section
    $io->section('Creating new users');

    // Text
    $io->text('Regular text message');
    $io->text([
        'Multiple lines',
        'of text',
    ]);

    // Listing
    $io->listing([
        'Item 1',
        'Item 2',
        'Item 3',
    ]);

    // Success, warning, error
    $io->success('Operation completed successfully!');
    $io->warning('This is a warning message');
    $io->error('An error occurred');
    $io->note('This is a note');
    $io->caution('Be careful!');

    // Info messages
    $io->info('Informational message');

    // Tables
    $io->table(
        ['Name', 'Email', 'Role'],
        [
            ['John', 'john@example.com', 'Admin'],
            ['Jane', 'jane@example.com', 'User'],
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
        'This is a title',
        ['foo1' => 'bar1'],
        ['foo2' => 'bar2'],
        ['foo3' => 'bar3'],
    );

    // Newlines
    $io->newLine();    // 1 line
    $io->newLine(3);   // 3 lines

    return Command::SUCCESS;
}
```

### Progress Indicators

```php
use Symfony\Component\Console\Style\SymfonyStyle;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $io->progressStart(100);

    for ($i = 0; $i < 100; $i++) {
        // Do some work
        usleep(50000);
        $io->progressAdvance();
    }

    $io->progressFinish();

    return Command::SUCCESS;
}
```

### Interactive Questions

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    // Ask simple question
    $name = $io->ask('What is your name?');
    $name = $io->ask('What is your name?', 'John'); // with default

    // Ask with validation
    $age = $io->ask('What is your age?', null, function ($answer) {
        if (!is_numeric($answer) || $answer < 0) {
            throw new \RuntimeException('Age must be a positive number');
        }
        return (int) $answer;
    });

    // Hidden input (for passwords)
    $password = $io->askHidden('Password');

    // Confirmation
    $confirm = $io->confirm('Do you want to continue?', true);

    // Choice
    $color = $io->choice(
        'Select your favorite color',
        ['Red', 'Blue', 'Green'],
        'Blue'  // default
    );

    return Command::SUCCESS;
}
```

---

## 7. UI Helpers

### Progress Bar

```php
use Symfony\Component\Console\Helper\ProgressBar;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $users = $this->userRepository->findAll();

    $progressBar = new ProgressBar($output, count($users));
    $progressBar->start();

    foreach ($users as $user) {
        // Process user
        $this->processUser($user);

        $progressBar->advance();
    }

    $progressBar->finish();
    $output->writeln('');  // New line after progress bar

    return Command::SUCCESS;
}
```

### Customizing Progress Bar

```php
$progressBar = new ProgressBar($output, 100);

// Set format
$progressBar->setFormat('verbose');
// Built-in formats: normal, verbose, very_verbose, debug

// Custom format
$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

// Set bar character
$progressBar->setBarCharacter('<fg=green>=</>');
$progressBar->setEmptyBarCharacter('-');
$progressBar->setProgressCharacter('>');

// Set width
$progressBar->setBarWidth(50);

// Custom messages
$progressBar->setMessage('Starting...', 'status');
$progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %status%');

$progressBar->start();

foreach ($items as $item) {
    $progressBar->setMessage('Processing: ' . $item->getName(), 'status');
    $progressBar->advance();
}

$progressBar->finish();
```

### Table Helper

```php
use Symfony\Component\Console\Helper\Table;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $table = new Table($output);

    $table
        ->setHeaders(['ID', 'Name', 'Email', 'Role'])
        ->setRows([
            [1, 'John Doe', 'john@example.com', 'Admin'],
            [2, 'Jane Smith', 'jane@example.com', 'User'],
            [3, 'Bob Johnson', 'bob@example.com', 'Editor'],
        ])
    ;

    $table->render();

    return Command::SUCCESS;
}
```

### Advanced Table Features

```php
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

$table = new Table($output);

$table->setHeaders([
    [new TableCell('User Information', ['colspan' => 3])],
    ['ID', 'Name', 'Email'],
]);

$table->setRows([
    [1, 'John', 'john@example.com'],
    new TableSeparator(),
    [2, 'Jane', 'jane@example.com'],
    new TableSeparator(),
    [new TableCell('Total: 2 users', ['colspan' => 3])],
]);

// Set style
$table->setStyle('box');
// Available styles: default, compact, borderless, box, box-double

$table->render();
```

---

## 8. Interactive Commands

### Using the Question Helper

```php
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $helper = $this->getHelper('question');

    // Simple question
    $question = new Question('Please enter your name: ', 'Guest');
    $name = $helper->ask($input, $output, $question);

    // Hidden question (password)
    $question = new Question('Password: ');
    $question->setHidden(true);
    $question->setHiddenFallback(false);
    $password = $helper->ask($input, $output, $question);

    // Choice question
    $question = new ChoiceQuestion(
        'Please select your role',
        ['Admin', 'Editor', 'User'],
        0  // default index
    );
    $role = $helper->ask($input, $output, $question);

    // Multiple choice
    $question = new ChoiceQuestion(
        'Select permissions (comma-separated)',
        ['read', 'write', 'delete'],
        '0,1'  // defaults
    );
    $question->setMultiselect(true);
    $permissions = $helper->ask($input, $output, $question);

    // Confirmation question
    $question = new ConfirmationQuestion('Continue? [Y/n] ', true);
    if (!$helper->ask($input, $output, $question)) {
        return Command::SUCCESS;
    }

    return Command::SUCCESS;
}
```

### Question Validation

```php
use Symfony\Component\Console\Question\Question;

$question = new Question('Email: ');
$question->setValidator(function ($answer) {
    if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException('Invalid email address');
    }
    return $answer;
});

$question->setMaxAttempts(3);

$email = $helper->ask($input, $output, $question);
```

### Autocomplete Questions

```php
$question = new Question('Country: ');
$question->setAutocompleterValues([
    'USA',
    'United Kingdom',
    'Germany',
    'France',
    'Spain',
]);

$country = $helper->ask($input, $output, $question);
```

### The interact() Method

```php
#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL)
            ->addArgument('email', InputArgument::OPTIONAL)
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        // Ask for missing arguments
        if (!$input->getArgument('username')) {
            $username = $io->ask('Username');
            $input->setArgument('username', $username);
        }

        if (!$input->getArgument('email')) {
            $email = $io->ask('Email');
            $input->setArgument('email', $email);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');

        // Create user...

        $io->success('User created successfully!');

        return Command::SUCCESS;
    }
}
```

---

## 9. Console Events

### Available Console Events

```php
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
```

### ConsoleCommandEvent

Triggered before command execution:

```php
namespace App\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class ConsoleCommandSubscriber implements EventSubscriberInterface
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
        $output = $event->getOutput();

        $this->logger->info('Command started', [
            'command' => $command->getName(),
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions(),
        ]);

        // Disable command execution
        // $event->disableCommand();

        // Or replace the command
        // $event->setCommand($anotherCommand);
    }
}
```

### ConsoleTerminateEvent

Triggered after command execution:

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

    $this->logger->info('Command finished', [
        'command' => $command->getName(),
        'exit_code' => $exitCode,
    ]);
}
```

### ConsoleErrorEvent

Triggered when command throws an exception:

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
        'error' => $error->getMessage(),
    ]);

    // Change the exit code
    $event->setExitCode(99);

    // Or replace the error
    // $event->setError(new \Exception('Custom error'));
}
```

### ConsoleSignalEvent

Handle system signals (PHP 8.1+):

```php
use Symfony\Component\Console\Event\ConsoleSignalEvent;

#[AsCommand(name: 'app:long-running')]
class LongRunningCommand extends Command
{
    public function getSubscribedSignals(): array
    {
        return [SIGINT, SIGTERM];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if ($signal === SIGINT) {
            // Handle Ctrl+C
            $this->output->writeln('Gracefully shutting down...');
            return Command::SUCCESS;
        }

        return false; // Continue with default behavior
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        while (true) {
            // Long-running process
            sleep(1);
        }

        return Command::SUCCESS;
    }
}
```

---

## 10. Verbosity Levels

### Available Verbosity Levels

```php
use Symfony\Component\Console\Output\OutputInterface;

OutputInterface::VERBOSITY_QUIET;         // -q or --quiet
OutputInterface::VERBOSITY_NORMAL;        // default
OutputInterface::VERBOSITY_VERBOSE;       // -v
OutputInterface::VERBOSITY_VERY_VERBOSE;  // -vv
OutputInterface::VERBOSITY_DEBUG;         // -vvv
```

### Using Verbosity Levels

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Always shown
    $output->writeln('This is always displayed');

    // Check verbosity level
    if ($output->isQuiet()) {
        return Command::SUCCESS;
    }

    if ($output->isVerbose()) {
        $output->writeln('Verbose output (-v)');
    }

    if ($output->isVeryVerbose()) {
        $output->writeln('Very verbose output (-vv)');
    }

    if ($output->isDebug()) {
        $output->writeln('Debug output (-vvv)');
    }

    // Verbosity-specific output
    $output->writeln('Normal', OutputInterface::VERBOSITY_NORMAL);
    $output->writeln('Verbose', OutputInterface::VERBOSITY_VERBOSE);
    $output->writeln('Very Verbose', OutputInterface::VERBOSITY_VERY_VERBOSE);
    $output->writeln('Debug', OutputInterface::VERBOSITY_DEBUG);

    return Command::SUCCESS;
}
```

### Setting Verbosity Programmatically

```php
$output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
```

---

## 11. Running Commands Programmatically

### Using the Application

```php
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SomeService
{
    public function __construct(
        private readonly Application $application,
    ) {}

    public function runCommand(): void
    {
        $input = new ArrayInput([
            'command' => 'app:import',
            'filename' => 'data.csv',
            '--format' => 'csv',
        ]);

        $output = new BufferedOutput();

        $exitCode = $this->application->doRun($input, $output);

        $content = $output->fetch();
    }
}
```

### Using CommandTester (for Testing)

```php
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

// In a service or controller
$kernel = self::bootKernel();
$application = new Application($kernel);

$command = $application->find('app:import');
$commandTester = new CommandTester($command);

$commandTester->execute([
    'filename' => 'test.csv',
    '--format' => 'csv',
]);

$output = $commandTester->getDisplay();
$exitCode = $commandTester->getStatusCode();
```

### Chaining Commands

```php
#[AsCommand(name: 'app:full-deploy')]
class FullDeployCommand extends Command
{
    public function __construct(
        private readonly Application $application,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commands = [
            ['command' => 'cache:clear'],
            ['command' => 'doctrine:migrations:migrate', '--no-interaction' => true],
            ['command' => 'assets:install'],
        ];

        foreach ($commands as $commandData) {
            $exitCode = $this->application->find($commandData['command'])
                ->run(new ArrayInput($commandData), $output);

            if ($exitCode !== Command::SUCCESS) {
                $output->writeln('<error>Command failed!</error>');
                return $exitCode;
            }
        }

        $output->writeln('<info>Deployment complete!</info>');

        return Command::SUCCESS;
    }
}
```

---

## 12. Testing Commands

### Basic Command Test

```php
namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:user:create');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);

        // Assert exit code
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Assert output
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('User created successfully', $output);
    }
}
```

### Testing Interactive Commands

```php
public function testInteractiveExecution(): void
{
    $application = new Application(self::bootKernel());
    $command = $application->find('app:user:create');
    $commandTester = new CommandTester($command);

    // Set inputs for interactive questions
    $commandTester->setInputs(['testuser', 'test@example.com']);

    $commandTester->execute([]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('testuser', $output);
}
```

### Testing with Options

```php
public function testExecuteWithOptions(): void
{
    $application = new Application(self::bootKernel());
    $command = $application->find('app:export');
    $commandTester = new CommandTester($command);

    $commandTester->execute([
        '--format' => 'json',
        '--limit' => 50,
        '--dry-run' => true,
    ]);

    $output = $commandTester->getDisplay();
    $this->assertStringContainsString('dry-run', $output);
}
```

### Testing Command Output Stream

```php
public function testOutputStream(): void
{
    $application = new Application(self::bootKernel());
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

---

## Best Practices

### 1. Use Descriptive Names

```php
// Good
#[AsCommand(name: 'app:user:create')]
#[AsCommand(name: 'app:cache:warmup')]
#[AsCommand(name: 'app:data:import')]

// Bad
#[AsCommand(name: 'create')]
#[AsCommand(name: 'warmup')]
#[AsCommand(name: 'import')]
```

### 2. Provide Good Help Text

```php
#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a new user account with specified credentials and role'
)]
class CreateUserCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp(<<<'EOT'
The <info>%command.name%</info> command creates a new user:

    <info>php %command.full_name% john john@example.com</info>

You can also specify a custom role:

    <info>php %command.full_name% admin admin@example.com --role=ROLE_ADMIN</info>

For more information, see the documentation at:
https://example.com/docs/user-management
EOT
        );
    }
}
```

### 3. Validate Input Early

```php
protected function initialize(InputInterface $input, OutputInterface $output): void
{
    $email = $input->getArgument('email');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new \InvalidArgumentException('Invalid email address');
    }
}
```

### 4. Use SymfonyStyle for Consistency

```php
// Consistent, professional output
$io = new SymfonyStyle($input, $output);
$io->title('User Management');
$io->success('Operation completed');
```

### 5. Handle Errors Gracefully

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    try {
        // Command logic
        $io->success('Success!');
        return Command::SUCCESS;
    } catch (\Exception $e) {
        $io->error($e->getMessage());

        if ($output->isVerbose()) {
            $io->writeln($e->getTraceAsString());
        }

        return Command::FAILURE;
    }
}
```

---

## Additional Resources

- [Symfony Console Component Documentation](https://symfony.com/doc/current/components/console.html)
- [Console Commands](https://symfony.com/doc/current/console.html)
- [How to Style Console Commands](https://symfony.com/doc/current/console/style.html)
- [How to Call Commands from Code](https://symfony.com/doc/current/console/calling_commands.html)
- [Console Events](https://symfony.com/doc/current/components/console/events.html)

---

## Next Steps

After mastering Console commands, explore:
- **Events** - Deep dive into the event dispatcher system
- **Services** - Advanced dependency injection patterns
- **Testing** - Comprehensive application testing strategies
- **Messenger** - Asynchronous message handling
