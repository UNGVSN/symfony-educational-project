# Console Component

## Overview and Purpose

The Console component allows you to create command-line commands. Console commands can be used for various tasks like database migrations, cache clearing, sending emails, and any other batch jobs.

**Key Benefits:**
- Build CLI applications easily
- Built-in input validation
- Formatted output with colors and styles
- Progress bars and tables
- Interactive prompts
- Auto-completion support

## Key Classes and Interfaces

### Core Classes

#### Application
The main application class that manages and runs commands.

#### Command
Base class for creating commands.

#### Input
Classes for handling command input (arguments and options).

**Input Classes:**
- `InputInterface` - Interface for input
- `InputArgument` - Represents a command argument
- `InputOption` - Represents a command option
- `ArrayInput` - Input from an array
- `StringInput` - Input from a string

#### Output
Classes for displaying output to the console.

**Output Classes:**
- `OutputInterface` - Interface for output
- `ConsoleOutput` - Standard console output
- `BufferedOutput` - Stores output in memory
- `NullOutput` - Discards all output

#### Style
Helper classes for formatted output.

**Style Classes:**
- `SymfonyStyle` - High-level output formatting
- `Table` - Display data in tables
- `ProgressBar` - Show progress indicators

## Common Use Cases

### 1. Basic Command

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:hello',
    description: 'Says hello to the user',
    aliases: ['hello']
)]
class HelloCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Hello from console!');

        return Command::SUCCESS;
    }
}
```

### 2. Command with Arguments and Options

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:greet',
    description: 'Greets someone'
)]
class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Who do you want to greet?'
            )
            ->addArgument(
                'last-name',
                InputArgument::OPTIONAL,
                'Your last name?'
            )
            ->addOption(
                'yell',
                'y',
                InputOption::VALUE_NONE,
                'If set, the message will be yelled in uppercase'
            )
            ->addOption(
                'iterations',
                'i',
                InputOption::VALUE_REQUIRED,
                'How many times should the message be printed?',
                1
            );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $name = $input->getArgument('name');
        $lastName = $input->getArgument('last-name');

        $fullName = $name;
        if ($lastName) {
            $fullName .= ' ' . $lastName;
        }

        $message = 'Hello, ' . $fullName;

        if ($input->getOption('yell')) {
            $message = strtoupper($message);
        }

        $iterations = (int) $input->getOption('iterations');

        for ($i = 0; $i < $iterations; $i++) {
            $output->writeln($message);
        }

        return Command::SUCCESS;
    }
}

// Usage:
// php bin/console app:greet John
// php bin/console app:greet John Doe --yell
// php bin/console app:greet John -y -i 3
```

### 3. Styled Output with SymfonyStyle

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:report',
    description: 'Generates a styled report'
)]
class ReportCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Title
        $io->title('System Report');

        // Success message
        $io->success('All systems operational');

        // Section
        $io->section('Database Statistics');

        // Table
        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Users', '1,234'],
                ['Active Sessions', '56'],
                ['Database Size', '2.3 GB'],
            ]
        );

        // Info box
        $io->info('This is an informational message');

        // Warning
        $io->warning('This is a warning message');

        // Error
        $io->error('This is an error message');

        // Note
        $io->note('This is a note');

        // Caution
        $io->caution('This is a caution message');

        // Lists
        $io->section('Pending Tasks');
        $io->listing([
            'Update dependencies',
            'Run migrations',
            'Clear cache',
        ]);

        // Horizontal line
        $io->horizontalTable(
            ['Name', 'Email', 'Role'],
            [
                ['John Doe', 'john@example.com', 'Admin'],
                ['Jane Smith', 'jane@example.com', 'User'],
            ]
        );

        // Text blocks
        $io->text([
            'Line 1 of text',
            'Line 2 of text',
            'Line 3 of text',
        ]);

        // Newline
        $io->newLine(2);

        // Comment
        $io->comment('This is a comment');

        return Command::SUCCESS;
    }
}
```

### 4. Interactive Input

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:interactive',
    description: 'Interactive command example'
)]
class InteractiveCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Simple question
        $name = $io->ask('What is your name?', 'Guest');

        // Hidden input (for passwords)
        $password = $io->askHidden('Enter your password');

        // Question with validation
        $email = $io->ask('What is your email?', null, function ($answer) {
            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email address');
            }
            return $answer;
        });

        // Confirmation question
        $continue = $io->confirm('Do you want to continue?', true);

        if (!$continue) {
            $io->warning('Operation cancelled');
            return Command::SUCCESS;
        }

        // Choice question
        $role = $io->choice(
            'Select your role',
            ['admin', 'user', 'guest'],
            'user'
        );

        // Multiple choice
        $interests = $io->choice(
            'Select your interests (comma-separated)',
            ['PHP', 'JavaScript', 'Python', 'Go', 'Rust'],
            null,
            true // Allow multiple selections
        );

        // Display results
        $io->section('Your Information');
        $io->listing([
            "Name: $name",
            "Email: $email",
            "Role: $role",
            "Interests: " . implode(', ', $interests),
        ]);

        return Command::SUCCESS;
    }
}
```

### 5. Progress Bar

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:process',
    description: 'Process items with progress bar'
)]
class ProcessCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->title('Processing Items');

        // Simple progress bar
        $items = range(1, 100);

        $io->progressStart(count($items));

        foreach ($items as $item) {
            // Simulate processing
            usleep(50000); // 50ms

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Advanced progress bar
        $io->newLine(2);
        $io->section('Advanced Progress Bar');

        $progressBar = new ProgressBar($output, 50);

        // Customize progress bar
        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%'
        );

        $progressBar->setBarCharacter('<fg=green>█</>');
        $progressBar->setEmptyBarCharacter('<fg=red>░</>');
        $progressBar->setProgressCharacter('<fg=green>█</>');

        $progressBar->start();

        for ($i = 0; $i < 50; $i++) {
            usleep(100000); // 100ms
            $progressBar->advance();
        }

        $progressBar->finish();

        $io->newLine(2);
        $io->success('Processing complete!');

        return Command::SUCCESS;
    }
}
```

### 6. Table Output

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:users',
    description: 'Display users table'
)]
class UsersCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        // Simple table using SymfonyStyle
        $io->table(
            ['ID', 'Name', 'Email', 'Status'],
            [
                [1, 'John Doe', 'john@example.com', 'Active'],
                [2, 'Jane Smith', 'jane@example.com', 'Active'],
                [3, 'Bob Johnson', 'bob@example.com', 'Inactive'],
            ]
        );

        // Advanced table with Table helper
        $table = new Table($output);
        $table->setHeaders(['ID', 'Product', 'Price', 'Stock']);

        $table->setRows([
            [1, 'Laptop', '$999.99', '15'],
            [2, 'Mouse', '$29.99', '50'],
            new TableSeparator(),
            [3, 'Keyboard', '$79.99', '30'],
            [4, 'Monitor', '$299.99', '8'],
        ]);

        // Set table style
        $table->setStyle('box');

        // Set column widths
        $table->setColumnWidths([5, 20, 10, 10]);

        // Set column styles
        $table->setColumnStyle(0, ['align' => 'center']);
        $table->setColumnStyle(2, ['align' => 'right']);

        $table->render();

        // Horizontal table
        $io->newLine();
        $io->horizontalTable(
            ['Name', 'Value'],
            [
                ['Total Sales', '$12,345.67'],
                ['Total Orders', '234'],
                ['Average Order', '$52.78'],
            ]
        );

        return Command::SUCCESS;
    }
}
```

### 7. Command with Dependency Injection

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use App\Service\UserService;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:sync-users',
    description: 'Synchronizes users from external API'
)]
class SyncUsersCommand extends Command
{
    public function __construct(
        private UserService $userService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->title('User Synchronization');

        try {
            $this->logger->info('Starting user synchronization');

            $users = $this->userService->fetchUsersFromApi();

            $io->progressStart(count($users));

            foreach ($users as $userData) {
                $this->userService->createOrUpdateUser($userData);
                $io->progressAdvance();
            }

            $io->progressFinish();

            $this->logger->info('User synchronization completed', [
                'count' => count($users)
            ]);

            $io->success(sprintf(
                'Successfully synchronized %d users',
                count($users)
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('User synchronization failed', [
                'error' => $e->getMessage()
            ]);

            $io->error('Synchronization failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
```

### 8. Command Events and Lifecycle

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:lifecycle',
    description: 'Demonstrates command lifecycle'
)]
class LifecycleCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp('This command demonstrates the lifecycle methods');
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ): void {
        // Called before interact() and execute()
        // Used to initialize properties based on input
        $output->writeln('1. Initialize phase');
    }

    protected function interact(
        InputInterface $input,
        OutputInterface $output
    ): void {
        // Called after initialize() and before execute()
        // Used to interact with user for missing arguments/options
        $io = new SymfonyStyle($input, $output);
        $io->note('2. Interact phase - ask for missing input');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        // Main command logic
        $output->writeln('3. Execute phase - main logic');

        return Command::SUCCESS;
    }
}
```

### 9. Calling Commands from Code

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:deploy',
    description: 'Deploy application'
)]
class DeployCommand extends Command
{
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $io->title('Deploying Application');

        // Call cache:clear command
        $io->section('Clearing cache...');
        $command = $this->getApplication()->find('cache:clear');
        $returnCode = $command->run(new ArrayInput([]), $output);

        if ($returnCode !== Command::SUCCESS) {
            $io->error('Failed to clear cache');
            return Command::FAILURE;
        }

        // Call assets:install command
        $io->section('Installing assets...');
        $command = $this->getApplication()->find('assets:install');
        $returnCode = $command->run(
            new ArrayInput(['--symlink' => true]),
            $output
        );

        if ($returnCode !== Command::SUCCESS) {
            $io->error('Failed to install assets');
            return Command::FAILURE;
        }

        // Call database:migrate command
        $io->section('Running migrations...');
        $command = $this->getApplication()->find('doctrine:migrations:migrate');
        $returnCode = $command->run(
            new ArrayInput(['--no-interaction' => true]),
            $output
        );

        if ($returnCode !== Command::SUCCESS) {
            $io->error('Failed to run migrations');
            return Command::FAILURE;
        }

        $io->success('Deployment completed successfully!');

        return Command::SUCCESS;
    }
}
```

### 10. Custom Console Application

```php
<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

// Create application
$application = new Application('My CLI App', '1.0.0');

// Add commands
$application->add(new class extends Command {
    protected static $defaultName = 'hello';

    protected function configure(): void
    {
        $this->setDescription('Says hello');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Hello from custom CLI!');
        return Command::SUCCESS;
    }
});

// Set default command
$application->setDefaultCommand('hello', true);

// Run application
$application->run();
```

## Code Examples

### Complete CLI Application

```php
<?php
// bin/console

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\Command\UserCreateCommand;
use App\Command\UserListCommand;
use App\Command\CacheClearCommand;

$application = new Application('My App CLI', '1.0.0');

// Register commands
$application->addCommands([
    new UserCreateCommand(),
    new UserListCommand(),
    new CacheClearCommand(),
]);

$application->run();
```

### Command with Input Validation

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:user:create',
    description: 'Creates a new user'
)]
class UserCreateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('admin', null, null, 'Set user as admin');
    }

    protected function interact(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $io = new SymfonyStyle($input, $output);

        // Validate email
        $email = $input->getArgument('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = $io->ask(
                'Please enter a valid email',
                null,
                function ($answer) {
                    if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException('Invalid email address');
                    }
                    return $answer;
                }
            );
            $input->setArgument('email', $email);
        }

        // Validate password
        $password = $input->getArgument('password');
        if (strlen($password) < 8) {
            $password = $io->askHidden(
                'Password must be at least 8 characters',
                function ($answer) {
                    if (strlen($answer) < 8) {
                        throw new \RuntimeException(
                            'Password must be at least 8 characters'
                        );
                    }
                    return $answer;
                }
            );
            $input->setArgument('password', $password);
        }
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $isAdmin = $input->getOption('admin');

        // Create user logic here
        $io->success(sprintf(
            'User created: %s (admin: %s)',
            $email,
            $isAdmin ? 'yes' : 'no'
        ));

        return Command::SUCCESS;
    }
}
```

## Links to Official Documentation

- [Console Component Documentation](https://symfony.com/doc/current/components/console.html)
- [Console Commands](https://symfony.com/doc/current/console.html)
- [Console Input](https://symfony.com/doc/current/console/input.html)
- [Console Output](https://symfony.com/doc/current/console/output.html)
- [Console Helpers](https://symfony.com/doc/current/components/console/helpers/index.html)
- [Progress Bar](https://symfony.com/doc/current/components/console/helpers/progressbar.html)
- [Table Helper](https://symfony.com/doc/current/components/console/helpers/table.html)
- [API Reference](https://api.symfony.com/master/Symfony/Component/Console.html)
