# Console Component - Practice Questions

Test your knowledge of Symfony's Console component with these comprehensive questions.

---

## Questions

### Question 1: Basic Command Structure

Create a command named `app:hello` that takes a required `name` argument and outputs "Hello, {name}!" using modern PHP 8.2+ syntax with attributes.

### Question 2: Arguments and Options

Create a command `app:user:list` with the following requirements:
- Optional `role` argument (defaults to 'ROLE_USER')
- `--limit` option with default value of 10
- `--format` option that accepts 'json' or 'table' (required)
- `--active-only` boolean flag

### Question 3: SymfonyStyle Output

Write a command that demonstrates different SymfonyStyle output methods:
- Title
- Section
- Success message
- Warning message
- Error message
- Table with user data

### Question 4: Interactive Command

Create a command `app:config:setup` that:
- Asks for database host (with default 'localhost')
- Asks for database name (required, with validation)
- Asks for username
- Asks for password (hidden input)
- Confirms before saving configuration

### Question 5: Progress Bar

Create a command that processes 100 items and displays a progress bar with custom formatting showing:
- Current/Total items
- Percentage
- Elapsed time
- Custom message showing the current item being processed

### Question 6: Command with Dependency Injection

Create a command `app:email:send` that:
- Injects `MailerInterface` and `UserRepository`
- Accepts a `--user-id` option
- Fetches the user from the repository
- Sends an email using the mailer
- Handles errors appropriately

### Question 7: Array Arguments and Options

Create a command that:
- Accepts multiple filenames as array arguments
- Has a `--exclude` array option for file patterns to exclude
- Processes each file unless it matches an exclude pattern

### Question 8: Command Lifecycle Methods

Write a command that uses all lifecycle methods (configure, initialize, interact, execute) to:
- Define a `filename` argument
- Validate in `initialize()` that the file exists
- Ask for confirmation in `interact()` if file is large (>1MB)
- Process the file in `execute()`

### Question 9: Verbosity Levels

Create a command that outputs different messages based on verbosity:
- Normal: Basic operation status
- Verbose (-v): Detailed processing information
- Very Verbose (-vv): SQL queries being executed
- Debug (-vvv): Memory usage and timing information

### Question 10: Custom Table

Create a command that displays a table with:
- A header row with colspan
- User data rows
- A separator between groups
- A footer row showing total count with colspan
- Box-double style

### Question 11: Question Validation

Create a command that asks for an email address with:
- Custom validation ensuring valid email format
- Normalization to lowercase
- Maximum 3 attempts
- Custom error message

### Question 12: Choice Questions

Write a command that:
- Asks user to select a country from a list
- Asks user to select multiple permissions (multiselect)
- Uses autocomplete for the country selection

### Question 13: Console Events

Create an event subscriber that:
- Logs when any command starts (ConsoleCommandEvent)
- Logs when any command finishes (ConsoleTerminateEvent)
- Sends an alert if a command fails (ConsoleErrorEvent)
- Includes command name, arguments, and exit code in logs

### Question 14: Running Commands Programmatically

Create a command `app:full-import` that runs these commands in sequence:
1. `cache:clear`
2. `app:import:users --source=csv`
3. `app:import:products --source=json`

Stop execution and return an error if any command fails.

### Question 15: Testing Commands

Write a PHPUnit test for a command `app:user:create` that:
- Tests successful user creation
- Tests with invalid email (should fail)
- Tests interactive mode with preset inputs
- Asserts correct output messages
- Verifies the command returns SUCCESS status code

### Question 16: Signal Handling

Create a long-running command that:
- Processes items in an infinite loop
- Handles SIGINT (Ctrl+C) to gracefully shutdown
- Handles SIGUSR1 to reload configuration
- Properly cleans up resources on shutdown

### Question 17: Negatable Options

Create a command with a `--colors` negatable option that:
- Defaults to true (colors enabled)
- Can be disabled with `--no-colors`
- Changes output formatting based on this option

### Question 18: Custom Output Formatting

Create a command that:
- Defines custom output formatter styles
- Uses these styles for different message types
- Handles both decorated (colored) and non-decorated output

### Question 19: Command Aliases

Create a command `app:database:migrate` with:
- Aliases: `db:migrate`, `migrate`
- Description explaining what the command does
- Help text with usage examples

### Question 20: Complex Validation

Create a command that validates:
- A date argument must be in YYYY-MM-DD format and in the future
- A `--count` option must be between 1 and 1000
- A `--format` option must be one of: json, xml, csv
- All validation happens in the `initialize()` method with clear error messages

---

## Answers

### Answer 1: Basic Command Structure

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:hello',
    description: 'Greets a person by name'
)]
class HelloCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name to greet');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln(sprintf('Hello, %s!', $name));

        return Command::SUCCESS;
    }
}
```

**Usage:**
```bash
php bin/console app:hello John
# Output: Hello, John!
```

---

### Answer 2: Arguments and Options

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:list',
    description: 'List users with filtering options'
)]
class UserListCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'role',
                InputArgument::OPTIONAL,
                'Filter by role',
                'ROLE_USER'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of users to display',
                10
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (json or table)'
            )
            ->addOption(
                'active-only',
                null,
                InputOption::VALUE_NONE,
                'Show only active users'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = $input->getArgument('role');
        $limit = (int) $input->getOption('limit');
        $format = $input->getOption('format');
        $activeOnly = $input->getOption('active-only');

        // Validate format
        if (!in_array($format, ['json', 'table'])) {
            $output->writeln('<error>Format must be "json" or "table"</error>');
            return Command::INVALID;
        }

        // Fetch users logic here...
        $users = []; // Simulated user data

        if ($format === 'json') {
            $output->writeln(json_encode($users, JSON_PRETTY_PRINT));
        } else {
            // Table output
            $output->writeln(sprintf(
                'Showing %d users with role %s%s',
                $limit,
                $role,
                $activeOnly ? ' (active only)' : ''
            ));
        }

        return Command::SUCCESS;
    }
}
```

**Usage:**
```bash
php bin/console app:user:list ROLE_ADMIN --limit=20 --format=json --active-only
```

---

### Answer 3: SymfonyStyle Output

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:style:demo',
    description: 'Demonstrates SymfonyStyle output methods'
)]
class StyleDemoCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Title
        $io->title('User Management System');

        // Section
        $io->section('User Report');

        // Success message
        $io->success('Successfully loaded 150 users from database');

        // Warning message
        $io->warning('5 users have expired passwords and need to reset them');

        // Error message
        $io->error('Failed to connect to external API');

        // Table with user data
        $io->table(
            ['ID', 'Name', 'Email', 'Status'],
            [
                [1, 'John Doe', 'john@example.com', 'Active'],
                [2, 'Jane Smith', 'jane@example.com', 'Active'],
                [3, 'Bob Johnson', 'bob@example.com', 'Inactive'],
            ]
        );

        // Note
        $io->note('This is a demonstration of SymfonyStyle output methods');

        // Caution
        $io->caution('Modifying user data requires administrator privileges');

        return Command::SUCCESS;
    }
}
```

---

### Answer 4: Interactive Command

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:config:setup',
    description: 'Interactive database configuration setup'
)]
class ConfigSetupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Database Configuration Setup');

        // Ask for database host with default
        $host = $io->ask('Database host', 'localhost');

        // Ask for database name with validation
        $dbName = $io->ask('Database name', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Database name cannot be empty');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $answer)) {
                throw new \RuntimeException(
                    'Database name can only contain letters, numbers, and underscores'
                );
            }
            return $answer;
        });

        // Ask for username
        $username = $io->ask('Database username');

        // Ask for password (hidden)
        $password = $io->askHidden('Database password');

        // Display summary
        $io->section('Configuration Summary');
        $io->table(
            ['Setting', 'Value'],
            [
                ['Host', $host],
                ['Database', $dbName],
                ['Username', $username],
                ['Password', str_repeat('*', strlen($password))],
            ]
        );

        // Confirm before saving
        if (!$io->confirm('Save this configuration?', true)) {
            $io->warning('Configuration not saved');
            return Command::SUCCESS;
        }

        // Save configuration (simulate)
        file_put_contents(
            '.env.local',
            sprintf(
                "DATABASE_URL=\"mysql://%s:%s@%s:3306/%s\"\n",
                $username,
                $password,
                $host,
                $dbName
            ),
            FILE_APPEND
        );

        $io->success('Configuration saved successfully!');

        return Command::SUCCESS;
    }
}
```

---

### Answer 5: Progress Bar

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:process:items',
    description: 'Process items with progress bar'
)]
class ProcessItemsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = range(1, 100);

        $progressBar = new ProgressBar($output, count($items));

        // Customize progress bar
        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% ⏱  %elapsed:6s% -- %message%'
        );
        $progressBar->setBarCharacter('<fg=green>█</>');
        $progressBar->setEmptyBarCharacter('░');
        $progressBar->setProgressCharacter('<fg=green>█</>');
        $progressBar->setMessage('Starting...', 'message');

        $progressBar->start();

        foreach ($items as $item) {
            // Update message
            $progressBar->setMessage(sprintf('Processing item #%d', $item), 'message');

            // Simulate work
            usleep(50000); // 50ms

            $progressBar->advance();
        }

        $progressBar->setMessage('Complete!', 'message');
        $progressBar->finish();

        $output->writeln('');
        $output->writeln('<info>All items processed successfully!</info>');

        return Command::SUCCESS;
    }
}
```

---

### Answer 6: Command with Dependency Injection

```php
namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:email:send',
    description: 'Send email to a specific user'
)]
class SendEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'user-id',
            'u',
            InputOption::VALUE_REQUIRED,
            'User ID to send email to'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userId = $input->getOption('user-id');

        if (!$userId) {
            $io->error('User ID is required. Use --user-id=123');
            return Command::INVALID;
        }

        try {
            // Fetch user
            $user = $this->userRepository->find($userId);

            if (!$user) {
                $io->error(sprintf('User with ID %d not found', $userId));
                return Command::FAILURE;
            }

            // Send email
            $email = (new Email())
                ->from('noreply@example.com')
                ->to($user->getEmail())
                ->subject('Important Notification')
                ->text('This is an important notification for you.')
                ->html('<p>This is an <strong>important</strong> notification for you.</p>');

            $this->mailer->send($email);

            $io->success(sprintf(
                'Email sent successfully to %s (%s)',
                $user->getName(),
                $user->getEmail()
            ));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to send email: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
```

---

### Answer 7: Array Arguments and Options

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:file:process',
    description: 'Process multiple files with exclude patterns'
)]
class ProcessFilesCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'filenames',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Files to process (separate multiple with space)'
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'File patterns to exclude'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filenames = $input->getArgument('filenames');
        $excludePatterns = $input->getOption('exclude');

        $io->title('File Processing');

        $processed = 0;
        $skipped = 0;

        foreach ($filenames as $filename) {
            // Check if file matches any exclude pattern
            $shouldExclude = false;
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $filename)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                $io->writeln(sprintf('<comment>Skipping: %s (matches exclude pattern)</comment>', $filename));
                $skipped++;
                continue;
            }

            // Process file
            $io->writeln(sprintf('<info>Processing: %s</info>', $filename));
            $this->processFile($filename);
            $processed++;
        }

        $io->newLine();
        $io->success(sprintf(
            'Processed %d files, skipped %d files',
            $processed,
            $skipped
        ));

        return Command::SUCCESS;
    }

    private function processFile(string $filename): void
    {
        // File processing logic here
        usleep(100000); // Simulate work
    }
}
```

**Usage:**
```bash
php bin/console app:file:process file1.txt file2.csv file3.log --exclude="*.log" --exclude="*.tmp"
```

---

### Answer 8: Command Lifecycle Methods

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:file:analyze',
    description: 'Analyze a file with validation and confirmation'
)]
class FileAnalyzeCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'filename',
            InputArgument::REQUIRED,
            'Path to the file to analyze'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $filename = $input->getArgument('filename');

        // Validate file exists
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException(
                sprintf('File "%s" does not exist', $filename)
            );
        }

        // Validate file is readable
        if (!is_readable($filename)) {
            throw new \InvalidArgumentException(
                sprintf('File "%s" is not readable', $filename)
            );
        }

        $output->writeln(
            sprintf('<info>File validated: %s</info>', $filename),
            OutputInterface::VERBOSITY_VERBOSE
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        // Check file size
        $fileSize = filesize($filename);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        if ($fileSize > 1024 * 1024) { // > 1MB
            $io->warning(sprintf(
                'File is large (%s MB). Processing may take some time.',
                $fileSizeMB
            ));

            if (!$io->confirm('Do you want to continue?', true)) {
                throw new \RuntimeException('Operation cancelled by user');
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('filename');

        $io->title('File Analysis');

        // Analyze file
        $io->section('File Information');
        $io->table(
            ['Property', 'Value'],
            [
                ['Name', basename($filename)],
                ['Path', realpath($filename)],
                ['Size', $this->formatBytes(filesize($filename))],
                ['Modified', date('Y-m-d H:i:s', filemtime($filename))],
                ['Type', mime_content_type($filename)],
            ]
        );

        // Process file content
        $io->section('Content Analysis');
        $lines = count(file($filename));
        $content = file_get_contents($filename);
        $words = str_word_count($content);
        $chars = strlen($content);

        $io->listing([
            sprintf('Lines: %d', $lines),
            sprintf('Words: %d', $words),
            sprintf('Characters: %d', $chars),
        ]);

        $io->success('File analysis complete!');

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

---

### Answer 9: Verbosity Levels

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:data:import',
    description: 'Import data with different verbosity levels'
)]
class DataImportCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Always shown (except in quiet mode)
        $output->writeln('<info>Starting data import...</info>');

        // Simulate data import
        $records = range(1, 100);

        foreach ($records as $i => $record) {
            // Normal: Basic progress
            if ($i % 25 === 0) {
                $output->writeln(sprintf('Processed %d records...', $i));
            }

            // Verbose: Detailed processing information
            $output->writeln(
                sprintf('Processing record #%d', $record),
                OutputInterface::VERBOSITY_VERBOSE
            );

            // Very Verbose: SQL queries
            $output->writeln(
                sprintf(
                    'Executing: INSERT INTO records (id, data) VALUES (%d, \'data_%d\')',
                    $record,
                    $record
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE
            );

            // Debug: Memory and timing
            if ($output->isDebug() && $i % 10 === 0) {
                $currentMemory = memory_get_usage();
                $memoryDiff = $currentMemory - $startMemory;

                $output->writeln(sprintf(
                    '[DEBUG] Record #%d - Memory: %s (+%s)',
                    $record,
                    $this->formatBytes($currentMemory),
                    $this->formatBytes($memoryDiff)
                ));
            }

            usleep(10000); // Simulate work
        }

        // Always shown
        $output->writeln('<info>Import complete!</info>');

        // Verbose: Summary statistics
        if ($output->isVerbose()) {
            $duration = microtime(true) - $startTime;
            $output->writeln('');
            $output->writeln(sprintf('Total records: %d', count($records)));
            $output->writeln(sprintf('Duration: %.2f seconds', $duration));
            $output->writeln(sprintf('Rate: %.2f records/sec', count($records) / $duration));
        }

        // Debug: Final memory usage
        if ($output->isDebug()) {
            $finalMemory = memory_get_usage();
            $peakMemory = memory_get_peak_usage();

            $output->writeln('');
            $output->writeln('[DEBUG] Memory Statistics:');
            $output->writeln(sprintf('  Start: %s', $this->formatBytes($startMemory)));
            $output->writeln(sprintf('  Final: %s', $this->formatBytes($finalMemory)));
            $output->writeln(sprintf('  Peak: %s', $this->formatBytes($peakMemory)));
            $output->writeln(sprintf('  Delta: %s', $this->formatBytes($finalMemory - $startMemory)));
        }

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

**Usage:**
```bash
php bin/console app:data:import              # Normal output
php bin/console app:data:import -v           # Verbose
php bin/console app:data:import -vv          # Very verbose
php bin/console app:data:import -vvv         # Debug
php bin/console app:data:import -q           # Quiet (no output)
```

---

### Answer 10: Custom Table

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:users:report',
    description: 'Display user report with custom table formatting'
)]
class UsersReportCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $table = new Table($output);

        // Set style
        $table->setStyle('box-double');

        // Headers with colspan
        $table->setHeaders([
            [new TableCell('User Management Report', ['colspan' => 4])],
            ['ID', 'Name', 'Email', 'Status'],
        ]);

        // Group 1: Admins
        $table->addRows([
            [new TableCell('Administrators', ['colspan' => 4])],
            new TableSeparator(),
            [1, 'John Doe', 'john@example.com', 'Active'],
            [2, 'Jane Smith', 'jane@example.com', 'Active'],
        ]);

        $table->addRow(new TableSeparator());

        // Group 2: Editors
        $table->addRows([
            [new TableCell('Editors', ['colspan' => 4])],
            new TableSeparator(),
            [3, 'Bob Johnson', 'bob@example.com', 'Active'],
            [4, 'Alice Williams', 'alice@example.com', 'Inactive'],
        ]);

        $table->addRow(new TableSeparator());

        // Group 3: Users
        $table->addRows([
            [new TableCell('Regular Users', ['colspan' => 4])],
            new TableSeparator(),
            [5, 'Charlie Brown', 'charlie@example.com', 'Active'],
            [6, 'Diana Prince', 'diana@example.com', 'Active'],
            [7, 'Eve Adams', 'eve@example.com', 'Active'],
        ]);

        $table->addRow(new TableSeparator());

        // Footer with total count
        $table->addRow([
            new TableCell('<info>Total Users: 7</info>', ['colspan' => 4]),
        ]);

        $table->render();

        return Command::SUCCESS;
    }
}
```

---

### Answer 11: Question Validation

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:register',
    description: 'Register a new user with email validation'
)]
class UserRegisterCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('User Registration');

        // Email question with validation
        $question = new Question('Email address: ');

        // Normalizer: convert to lowercase
        $question->setNormalizer(function ($value) {
            return strtolower(trim($value ?? ''));
        });

        // Validator: ensure valid email format
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Email address cannot be empty');
            }

            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException(
                    'Invalid email format. Please enter a valid email address (e.g., user@example.com)'
                );
            }

            return $answer;
        });

        // Maximum 3 attempts
        $question->setMaxAttempts(3);

        try {
            $email = $helper->ask($input, $output, $question);

            $io->success(sprintf('User registered with email: %s', $email));

            return Command::SUCCESS;

        } catch (\RuntimeException $e) {
            $io->error('Failed to register user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

---

### Answer 12: Choice Questions

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:preferences',
    description: 'Set user preferences with choice questions'
)]
class UserPreferencesCommand extends Command
{
    private array $countries = [
        'United States',
        'United Kingdom',
        'Germany',
        'France',
        'Spain',
        'Italy',
        'Canada',
        'Australia',
        'Japan',
        'China',
    ];

    private array $permissions = [
        'read',
        'write',
        'delete',
        'admin',
        'export',
        'import',
    ];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('User Preferences Setup');

        // Country selection with autocomplete
        $countryQuestion = new Question('Country: ');
        $countryQuestion->setAutocompleterValues($this->countries);

        $country = $helper->ask($input, $output, $countryQuestion);

        $io->writeln(sprintf('<info>Selected country: %s</info>', $country));

        // Multiple permissions selection
        $permissionsQuestion = new ChoiceQuestion(
            'Select permissions (separate multiple with comma)',
            $this->permissions,
            '0' // default to 'read'
        );
        $permissionsQuestion->setMultiselect(true);

        $selectedPermissions = $helper->ask($input, $output, $permissionsQuestion);

        // Display summary
        $io->newLine();
        $io->section('Summary');

        $io->table(
            ['Setting', 'Value'],
            [
                ['Country', $country],
                ['Permissions', implode(', ', $selectedPermissions)],
            ]
        );

        $io->success('Preferences saved successfully!');

        return Command::SUCCESS;
    }
}
```

---

### Answer 13: Console Events

```php
namespace App\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AlertServiceInterface $alertService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => 'onConsoleCommand',
            ConsoleTerminateEvent::class => 'onConsoleTerminate',
            ConsoleErrorEvent::class => 'onConsoleError',
        ];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        $this->logger->info('Console command started', [
            'command' => $command->getName(),
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions(),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        $exitCode = $event->getExitCode();

        $level = $exitCode === 0 ? 'info' : 'warning';

        $this->logger->log($level, 'Console command finished', [
            'command' => $command->getName(),
            'exit_code' => $exitCode,
            'status' => $exitCode === 0 ? 'success' : 'failure',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $error = $event->getError();
        $exitCode = $event->getExitCode();

        $this->logger->error('Console command error', [
            'command' => $command->getName(),
            'error_message' => $error->getMessage(),
            'error_class' => get_class($error),
            'exit_code' => $exitCode,
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        // Send alert for critical commands
        $criticalCommands = ['app:deploy', 'app:backup', 'app:migrate'];

        if (in_array($command->getName(), $criticalCommands)) {
            $this->alertService->send(sprintf(
                'CRITICAL: Command "%s" failed with error: %s',
                $command->getName(),
                $error->getMessage()
            ));
        }
    }
}
```

---

### Answer 14: Running Commands Programmatically

```php
namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:full-import',
    description: 'Run complete import process (users and products)'
)]
class FullImportCommand extends Command
{
    public function __construct(
        private readonly Application $application,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Full Import Process');

        // Commands to run in sequence
        $commands = [
            [
                'name' => 'cache:clear',
                'arguments' => [],
                'description' => 'Clearing cache',
            ],
            [
                'name' => 'app:import:users',
                'arguments' => ['--source' => 'csv'],
                'description' => 'Importing users',
            ],
            [
                'name' => 'app:import:products',
                'arguments' => ['--source' => 'json'],
                'description' => 'Importing products',
            ],
        ];

        foreach ($commands as $commandConfig) {
            $io->section($commandConfig['description']);

            $command = $this->application->find($commandConfig['name']);

            $arguments = array_merge(
                ['command' => $commandConfig['name']],
                $commandConfig['arguments']
            );

            $commandInput = new ArrayInput($arguments);
            $commandInput->setInteractive(false);

            $exitCode = $command->run($commandInput, $output);

            if ($exitCode !== Command::SUCCESS) {
                $io->error(sprintf(
                    'Command "%s" failed with exit code %d',
                    $commandConfig['name'],
                    $exitCode
                ));

                return Command::FAILURE;
            }

            $io->success(sprintf('✓ %s completed', $commandConfig['description']));
        }

        $io->newLine();
        $io->success('Full import process completed successfully!');

        return Command::SUCCESS;
    }
}
```

---

### Answer 15: Testing Commands

```php
namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:user:create');
        $this->commandTester = new CommandTester($command);
    }

    public function testSuccessfulUserCreation(): void
    {
        // Execute command
        $this->commandTester->execute([
            'username' => 'johndoe',
            'email' => 'john@example.com',
            '--role' => 'ROLE_USER',
        ]);

        // Assert exit code
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Assert output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('User created successfully', $output);
        $this->assertStringContainsString('johndoe', $output);
    }

    public function testInvalidEmail(): void
    {
        // Execute command with invalid email
        $this->commandTester->execute([
            'username' => 'johndoe',
            'email' => 'invalid-email',
        ]);

        // Assert failure
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        // Assert error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Invalid email', $output);
    }

    public function testInteractiveMode(): void
    {
        // Set inputs for interactive questions
        $this->commandTester->setInputs([
            'johndoe',              // username
            'john@example.com',     // email
            'ROLE_USER',           // role
            'y',                   // confirmation
        ]);

        // Execute without arguments (interactive mode)
        $this->commandTester->execute([]);

        // Assert success
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Assert output contains expected text
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('What is your username?', $output);
        $this->assertStringContainsString('User created successfully', $output);
    }

    public function testVerboseOutput(): void
    {
        // Execute with verbose flag
        $this->commandTester->execute(
            [
                'username' => 'johndoe',
                'email' => 'john@example.com',
            ],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        // Assert verbose output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Creating user in database', $output);
    }

    public function testDuplicateUsername(): void
    {
        // Create user first time
        $this->commandTester->execute([
            'username' => 'johndoe',
            'email' => 'john1@example.com',
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Try to create same username again
        $this->commandTester->execute([
            'username' => 'johndoe',
            'email' => 'john2@example.com',
        ]);

        // Should fail
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('already exists', $output);
    }
}
```

---

### Answer 16: Signal Handling

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:worker:process',
    description: 'Long-running worker process with signal handling'
)]
class WorkerProcessCommand extends Command implements SignalableCommandInterface
{
    private bool $shouldStop = false;
    private array $config = [];
    private ?SymfonyStyle $io = null;

    public function getSubscribedSignals(): array
    {
        // Subscribe to signals
        return [SIGINT, SIGTERM, SIGUSR1];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        switch ($signal) {
            case SIGINT:
                // Ctrl+C pressed
                $this->io?->warning('Received SIGINT (Ctrl+C). Shutting down gracefully...');
                $this->shouldStop = true;
                $this->cleanup();
                return Command::SUCCESS;

            case SIGTERM:
                // Termination signal
                $this->io?->warning('Received SIGTERM. Shutting down gracefully...');
                $this->shouldStop = true;
                $this->cleanup();
                return Command::SUCCESS;

            case SIGUSR1:
                // Custom signal - reload configuration
                $this->io?->note('Received SIGUSR1. Reloading configuration...');
                $this->reloadConfig();
                return false; // Continue execution

            default:
                return false;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Worker Process');
        $this->io->note('Press Ctrl+C to stop gracefully');
        $this->io->note('Send SIGUSR1 to reload config: kill -SIGUSR1 ' . getmypid());

        // Load initial configuration
        $this->loadConfig();

        $processed = 0;

        while (!$this->shouldStop) {
            $this->io->writeln(
                sprintf('[%s] Processing job #%d...', date('Y-m-d H:i:s'), ++$processed),
                OutputInterface::VERBOSITY_VERBOSE
            );

            // Simulate work
            $this->processJob();

            // Show progress every 10 jobs
            if ($processed % 10 === 0) {
                $this->io->writeln(sprintf('Processed %d jobs', $processed));
            }

            // Sleep briefly
            sleep(1);
        }

        $this->io->success(sprintf('Worker stopped. Processed %d jobs total.', $processed));

        return Command::SUCCESS;
    }

    private function loadConfig(): void
    {
        $this->config = [
            'batch_size' => 10,
            'timeout' => 30,
            'retry_attempts' => 3,
        ];

        $this->io?->writeln('Configuration loaded', OutputInterface::VERBOSITY_VERBOSE);
    }

    private function reloadConfig(): void
    {
        $this->loadConfig();
        $this->io?->success('Configuration reloaded successfully');
    }

    private function processJob(): void
    {
        // Simulate job processing
        usleep(100000); // 100ms
    }

    private function cleanup(): void
    {
        $this->io?->writeln('Cleaning up resources...', OutputInterface::VERBOSITY_VERBOSE);

        // Close connections, flush buffers, etc.

        $this->io?->writeln('Cleanup complete', OutputInterface::VERBOSITY_VERBOSE);
    }
}
```

**Usage:**
```bash
# Start worker
php bin/console app:worker:process -v

# In another terminal, reload config:
kill -SIGUSR1 $(pgrep -f "app:worker:process")

# Gracefully stop:
# Press Ctrl+C or: kill -SIGTERM $(pgrep -f "app:worker:process")
```

---

### Answer 17: Negatable Options

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:report:generate',
    description: 'Generate a report with optional color output'
)]
class GenerateReportCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'colors',
            null,
            InputOption::VALUE_NEGATABLE,
            'Enable or disable colored output',
            true  // default to enabled
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $useColors = $input->getOption('colors');

        // Disable output decoration if colors are disabled
        if (!$useColors) {
            $output->setDecorated(false);
        }

        $output->writeln('<info>========================================</info>');
        $output->writeln('<info>           Report Summary             </info>');
        $output->writeln('<info>========================================</info>');
        $output->writeln('');

        $output->writeln('<comment>Processing data...</comment>');
        $output->writeln('');

        $output->writeln('<fg=green>✓</> Success items: 150');
        $output->writeln('<fg=yellow>⚠</> Warning items: 12');
        $output->writeln('<fg=red>✗</> Error items: 3');
        $output->writeln('');

        $output->writeln('<info>Total: 165 items processed</info>');

        if ($useColors) {
            $output->writeln('');
            $output->writeln('<comment>(Colors enabled)</comment>');
        } else {
            $output->writeln('');
            $output->writeln('(Colors disabled - plain text output)');
        }

        return Command::SUCCESS;
    }
}
```

**Usage:**
```bash
php bin/console app:report:generate           # With colors (default)
php bin/console app:report:generate --colors   # Explicitly enable colors
php bin/console app:report:generate --no-colors # Disable colors
```

---

### Answer 18: Custom Output Formatting

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:status:display',
    description: 'Display status with custom formatting'
)]
class DisplayStatusCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Define custom styles
        $formatter = $output->getFormatter();

        // Fire style - critical errors
        $fireStyle = new OutputFormatterStyle('red', 'yellow', ['bold', 'blink']);
        $formatter->setStyle('fire', $fireStyle);

        // Success style - bright green
        $successStyle = new OutputFormatterStyle('green', null, ['bold']);
        $formatter->setStyle('success', $successStyle);

        // Highlight style - blue background
        $highlightStyle = new OutputFormatterStyle('white', 'blue', ['bold']);
        $formatter->setStyle('highlight', $highlightStyle);

        // Muted style - gray text
        $mutedStyle = new OutputFormatterStyle('white', null, []);
        $formatter->setStyle('muted', $mutedStyle);

        // Use custom styles
        $output->writeln('<highlight>System Status Report</highlight>');
        $output->writeln('');

        $output->writeln('<success>✓ Database connection: OK</success>');
        $output->writeln('<success>✓ Cache service: OK</success>');
        $output->writeln('<success>✓ API endpoint: OK</success>');
        $output->writeln('');

        $output->writeln('<comment>⚠ Queue processing: Degraded (3 workers)</comment>');
        $output->writeln('');

        $output->writeln('<fire>✗ Critical: Disk space low (5% remaining)</fire>');
        $output->writeln('');

        $output->writeln('<muted>Last checked: ' . date('Y-m-d H:i:s') . '</muted>');

        // Handle non-decorated output
        if (!$output->isDecorated()) {
            $output->writeln('');
            $output->writeln('Note: This terminal does not support colors.');
            $output->writeln('      Output shown in plain text.');
        }

        return Command::SUCCESS;
    }
}
```

---

### Answer 19: Command Aliases

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database:migrate',
    description: 'Run database migrations to update schema',
    aliases: ['db:migrate', 'migrate']
)]
class DatabaseMigrateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show migrations that would be executed without running them'
            )
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command runs database migrations:

    <info>php %command.full_name%</info>

You can also use the shorter aliases:

    <info>php bin/console db:migrate</info>
    <info>php bin/console migrate</info>

To preview migrations without executing them:

    <info>php %command.full_name% --dry-run</info>

For more information about migrations, see:
https://symfony.com/doc/current/doctrine.html#migrations
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $io->title('Database Migrations');

        if ($isDryRun) {
            $io->note('DRY RUN MODE - No migrations will be executed');
        }

        // Migration logic here...

        $io->success('Migrations completed successfully');

        return Command::SUCCESS;
    }
}
```

**Usage:**
```bash
php bin/console app:database:migrate
php bin/console db:migrate
php bin/console migrate

# All three commands work the same way
```

---

### Answer 20: Complex Validation

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export:data',
    description: 'Export data with comprehensive validation'
)]
class ExportDataCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument(
                'date',
                InputArgument::REQUIRED,
                'Export date in YYYY-MM-DD format'
            )
            ->addOption(
                'count',
                'c',
                InputOption::VALUE_REQUIRED,
                'Number of records to export (1-1000)',
                100
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Export format: json, xml, or csv',
                'json'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        // Validate date argument
        $dateString = $input->getArgument('date');

        $date = \DateTime::createFromFormat('Y-m-d', $dateString);

        if (!$date || $date->format('Y-m-d') !== $dateString) {
            throw new \InvalidArgumentException(
                'Date must be in YYYY-MM-DD format (e.g., 2024-01-15)'
            );
        }

        // Ensure date is in the future
        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        if ($date <= $now) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Date must be in the future. Given: %s, Today: %s',
                    $date->format('Y-m-d'),
                    $now->format('Y-m-d')
                )
            );
        }

        // Validate count option
        $count = (int) $input->getOption('count');

        if ($count < 1 || $count > 1000) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Count must be between 1 and 1000. Given: %d',
                    $count
                )
            );
        }

        // Validate format option
        $validFormats = ['json', 'xml', 'csv'];
        $format = strtolower($input->getOption('format'));

        if (!in_array($format, $validFormats)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Format must be one of: %s. Given: %s',
                    implode(', ', $validFormats),
                    $format
                )
            );
        }

        // Update option with normalized value
        $input->setOption('format', $format);

        $io->writeln(
            '<info>All input validation passed</info>',
            OutputInterface::VERBOSITY_VERBOSE
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $date = $input->getArgument('date');
        $count = (int) $input->getOption('count');
        $format = $input->getOption('format');

        $io->title('Data Export');

        $io->table(
            ['Parameter', 'Value'],
            [
                ['Date', $date],
                ['Count', $count],
                ['Format', $format],
            ]
        );

        // Export logic here...

        $io->success(sprintf(
            'Exported %d records in %s format for date %s',
            $count,
            $format,
            $date
        ));

        return Command::SUCCESS;
    }
}
```

**Usage:**
```bash
# Valid usage
php bin/console app:export:data 2025-12-31 --count=500 --format=json

# Invalid date format
php bin/console app:export:data 12-31-2025
# Error: Date must be in YYYY-MM-DD format

# Date in the past
php bin/console app:export:data 2023-01-01
# Error: Date must be in the future

# Invalid count
php bin/console app:export:data 2025-12-31 --count=5000
# Error: Count must be between 1 and 1000

# Invalid format
php bin/console app:export:data 2025-12-31 --format=pdf
# Error: Format must be one of: json, xml, csv
```

---

## Summary

These questions cover all major aspects of the Symfony Console component:

1. Basic command structure with attributes
2. Arguments and options (all types)
3. SymfonyStyle output methods
4. Interactive commands with questions
5. Progress bars with customization
6. Dependency injection in commands
7. Array arguments and options
8. Complete command lifecycle
9. Verbosity levels
10. Advanced table formatting
11. Question validation and normalization
12. Choice questions and autocomplete
13. Console events
14. Running commands programmatically
15. Testing commands
16. Signal handling
17. Negatable options
18. Custom output formatting
19. Command aliases
20. Complex validation patterns

Practice these examples to master Symfony's Console component!
