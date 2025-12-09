# Console Component - Deep Dive

Advanced topics and techniques for mastering Symfony Console commands.

---

## Table of Contents

1. [Console Events Deep Dive](#console-events-deep-dive)
2. [Verbosity Levels and Output Management](#verbosity-levels-and-output-management)
3. [Running Commands Programmatically](#running-commands-programmatically)
4. [Testing Commands Comprehensively](#testing-commands-comprehensively)
5. [Lazy Command Loading](#lazy-command-loading)
6. [Signal Handling](#signal-handling)
7. [Completion Scripts](#completion-scripts)
8. [Custom Helpers](#custom-helpers)
9. [Custom Output Formatters](#custom-output-formatters)
10. [Command Locking](#command-locking)
11. [Performance Optimization](#performance-optimization)
12. [Advanced Patterns](#advanced-patterns)

---

## Console Events Deep Dive

### Understanding the Event Flow

```
Application Start
    │
    ├─→ ConsoleCommandEvent (before command execution)
    │   ├─ Can disable command
    │   ├─ Can replace command
    │   └─ Can modify input/output
    │
    ├─→ Command::initialize()
    ├─→ Command::interact()
    ├─→ Command::execute()
    │
    ├─→ ConsoleTerminateEvent (after command execution)
    │   ├─ Always dispatched (even on error)
    │   ├─ Can modify exit code
    │   └─ Can perform cleanup
    │
    └─→ ConsoleErrorEvent (on exception)
        ├─ Can replace exception
        ├─ Can modify exit code
        └─ Can suppress exception
```

### Complete Event Subscriber Example

```php
namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Notifier\NotifierInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
    private float $startTime;
    private int $memoryStart;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly NotifierInterface $notifier,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => 'onCommandStart',
            ConsoleTerminateEvent::class => 'onCommandTerminate',
            ConsoleErrorEvent::class => 'onCommandError',
        ];
    }

    public function onCommandStart(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        // Record metrics
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);

        // Log command start
        $this->logger->info('Console command started', [
            'command' => $command->getName(),
            'arguments' => $input->getArguments(),
            'options' => $input->getOptions(),
            'user' => get_current_user(),
            'pid' => getmypid(),
        ]);

        // Example: Prevent certain commands in production
        if ($_ENV['APP_ENV'] === 'prod' && str_starts_with($command->getName(), 'app:dev:')) {
            $event->disableCommand();
            $event->getOutput()->writeln('<error>Development commands are disabled in production</error>');
        }
    }

    public function onCommandTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        $exitCode = $event->getExitCode();

        // Calculate metrics
        $duration = microtime(true) - $this->startTime;
        $memoryUsed = memory_get_usage(true) - $this->memoryStart;
        $memoryPeak = memory_get_peak_usage(true);

        // Log command completion
        $this->logger->info('Console command finished', [
            'command' => $command->getName(),
            'exit_code' => $exitCode,
            'duration' => round($duration, 3),
            'memory_used' => $this->formatBytes($memoryUsed),
            'memory_peak' => $this->formatBytes($memoryPeak),
        ]);

        // Send metrics to monitoring system
        // $this->metrics->timing('console.command.duration', $duration, ['command' => $command->getName()]);

        // Notify on long-running commands
        if ($duration > 300) { // 5 minutes
            $this->notifier->send(
                sprintf('Long-running command "%s" took %.2f seconds', $command->getName(), $duration)
            );
        }
    }

    public function onCommandError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $error = $event->getError();

        // Log error with full context
        $this->logger->error('Console command error', [
            'command' => $command->getName(),
            'error_class' => get_class($error),
            'error_message' => $error->getMessage(),
            'error_code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
        ]);

        // Send error notification
        $this->notifier->send(
            sprintf('Command "%s" failed: %s', $command->getName(), $error->getMessage())
        );

        // Example: Transform certain exceptions
        if ($error instanceof \InvalidArgumentException) {
            $event->setError(new \RuntimeException(
                'Invalid input provided. Please check the documentation.',
                0,
                $error
            ));
            $event->setExitCode(2); // Invalid input code
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

### Conditional Event Logic

```php
namespace App\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $maintenanceFile,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => ['onCommand', 100], // High priority
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        if (!file_exists($this->maintenanceFile)) {
            return;
        }

        $command = $event->getCommand();
        $allowedCommands = [
            'cache:clear',
            'cache:warmup',
            'app:maintenance:disable',
        ];

        // Allow maintenance commands
        if (in_array($command->getName(), $allowedCommands)) {
            return;
        }

        // Block other commands
        $event->disableCommand();
        $event->getOutput()->writeln('<error>Application is in maintenance mode</error>');
    }
}
```

---

## Verbosity Levels and Output Management

### Advanced Verbosity Usage

```php
#[AsCommand(name: 'app:process')]
class ProcessCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Different output for different levels
        $io->writeln('Processing started'); // Always shown (except -q)

        if ($output->isVerbose()) {
            $io->section('Verbose Details');
            $io->text('Loading configuration...');
            $io->text('Connecting to database...');
        }

        if ($output->isVeryVerbose()) {
            $io->section('Very Verbose Details');
            $io->listing([
                'Configuration file: config.yaml',
                'Database host: localhost',
                'Database name: app_db',
            ]);
        }

        if ($output->isDebug()) {
            $io->section('Debug Information');
            $io->definitionList(
                ['Memory usage' => $this->formatBytes(memory_get_usage())],
                ['Peak memory' => $this->formatBytes(memory_get_peak_usage())],
                ['Time' => date('Y-m-d H:i:s')],
            );
        }

        // Specific verbosity level
        $output->writeln('This is only at normal level', OutputInterface::VERBOSITY_NORMAL);
        $output->writeln('Debug information', OutputInterface::VERBOSITY_DEBUG);

        return Command::SUCCESS;
    }
}
```

### Custom Verbosity Helpers

```php
trait VerbosityAwareTrait
{
    protected function writeVerbose(OutputInterface $output, string $message): void
    {
        if ($output->isVerbose()) {
            $output->writeln($message);
        }
    }

    protected function writeDebug(OutputInterface $output, string $message): void
    {
        if ($output->isDebug()) {
            $output->writeln('<comment>[DEBUG]</comment> ' . $message);
        }
    }

    protected function writeConditional(
        OutputInterface $output,
        string $message,
        int $minVerbosity = OutputInterface::VERBOSITY_VERBOSE
    ): void {
        if ($output->getVerbosity() >= $minVerbosity) {
            $output->writeln($message);
        }
    }
}
```

### Section Output for Progress Updates

```php
use Symfony\Component\Console\Output\ConsoleSectionOutput;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    if (!$output instanceof ConsoleOutput) {
        throw new \RuntimeException('This command requires ConsoleOutput');
    }

    $section1 = $output->section();
    $section2 = $output->section();

    $section1->writeln('Processing files...');

    for ($i = 1; $i <= 10; $i++) {
        sleep(1);
        $section2->overwrite("Progress: $i/10");
    }

    $section1->writeln('Complete!');
    $section2->clear();

    return Command::SUCCESS;
}
```

---

## Running Commands Programmatically

### Using the Console Application Service

```php
namespace App\Service;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class CommandRunner
{
    public function __construct(
        private readonly Application $application,
    ) {}

    public function run(string $command, array $arguments = [], bool $silent = false): int
    {
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        $input->setInteractive(false);

        $output = $silent ? new NullOutput() : new BufferedOutput();

        $this->application->setAutoExit(false);
        return $this->application->run($input, $output);
    }

    public function runAndGetOutput(string $command, array $arguments = []): string
    {
        $input = new ArrayInput(array_merge(['command' => $command], $arguments));
        $input->setInteractive(false);

        $output = new BufferedOutput();

        $this->application->setAutoExit(false);
        $this->application->run($input, $output);

        return $output->fetch();
    }

    public function runMultiple(array $commands): array
    {
        $results = [];

        foreach ($commands as $commandName => $arguments) {
            $exitCode = $this->run($commandName, $arguments);
            $results[$commandName] = $exitCode;

            if ($exitCode !== 0) {
                break; // Stop on first failure
            }
        }

        return $results;
    }
}
```

### Calling Commands from Controllers

```php
namespace App\Controller;

use App\Service\CommandRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(
        private readonly CommandRunner $commandRunner,
    ) {}

    #[Route('/admin/cache/clear', name: 'admin_cache_clear')]
    public function clearCache(): Response
    {
        $exitCode = $this->commandRunner->run('cache:clear');

        if ($exitCode === 0) {
            $this->addFlash('success', 'Cache cleared successfully');
        } else {
            $this->addFlash('error', 'Failed to clear cache');
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/import', name: 'admin_import')]
    public function import(): Response
    {
        $output = $this->commandRunner->runAndGetOutput('app:import', [
            'filename' => 'data.csv',
            '--format' => 'csv',
        ]);

        return new Response($output, 200, ['Content-Type' => 'text/plain']);
    }
}
```

### Command Orchestration

```php
#[AsCommand(name: 'app:deploy')]
class DeployCommand extends Command
{
    public function __construct(
        private readonly Application $application,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Deployment Started');

        $steps = [
            'Clearing cache' => ['cache:clear', '--no-warmup' => true],
            'Running migrations' => ['doctrine:migrations:migrate', '--no-interaction' => true],
            'Warming up cache' => ['cache:warmup'],
            'Installing assets' => ['assets:install', 'public'],
        ];

        $this->application->setAutoExit(false);

        foreach ($steps as $description => $commandData) {
            $io->section($description);

            $commandName = array_shift($commandData);
            $arguments = array_merge(['command' => $commandName], $commandData);

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $exitCode = $this->application->run($input, $output);

            if ($exitCode !== Command::SUCCESS) {
                $io->error("Step '$description' failed");
                $this->logger->error('Deployment failed', ['step' => $description]);
                return Command::FAILURE;
            }

            $io->success("Step '$description' completed");
        }

        $io->success('Deployment completed successfully!');

        return Command::SUCCESS;
    }
}
```

---

## Testing Commands Comprehensively

### Complete Test Example

```php
namespace App\Tests\Command;

use App\Command\ImportCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ImportCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:import');
        $this->commandTester = new CommandTester($command);

        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testSuccessfulImport(): void
    {
        $testFile = __DIR__ . '/fixtures/users.csv';

        $this->commandTester->execute([
            'filename' => $testFile,
            '--format' => 'csv',
        ]);

        // Assert exit code
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Assert output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Import completed', $output);
        $this->assertStringContainsString('3 users imported', $output);

        // Assert database state
        $users = $this->userRepository->findAll();
        $this->assertCount(3, $users);
    }

    public function testImportWithInvalidFile(): void
    {
        $this->commandTester->execute([
            'filename' => 'nonexistent.csv',
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('File not found', $output);
    }

    public function testImportWithDryRun(): void
    {
        $testFile = __DIR__ . '/fixtures/users.csv';

        $this->commandTester->execute([
            'filename' => $testFile,
            '--dry-run' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Assert no database changes
        $users = $this->userRepository->findAll();
        $this->assertCount(0, $users);

        // Assert output indicates dry run
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('DRY RUN', $output);
    }

    public function testVerboseOutput(): void
    {
        $testFile = __DIR__ . '/fixtures/users.csv';

        $this->commandTester->execute(
            ['filename' => $testFile],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Processing row', $output);
    }

    public function testInteractiveMode(): void
    {
        $this->commandTester->setInputs([
            __DIR__ . '/fixtures/users.csv', // filename
            'csv',                            // format
            'y',                              // confirm
        ]);

        $this->commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testErrorHandling(): void
    {
        $testFile = __DIR__ . '/fixtures/invalid.csv';

        $this->commandTester->execute(['filename' => $testFile]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $stderr = $this->commandTester->getErrorOutput();

        $this->assertStringContainsString('Error', $output);
    }

    protected function tearDown(): void
    {
        // Clean up database
        $this->entityManager->clear();
        parent::tearDown();
    }
}
```

### Testing with Mocks

```php
public function testCommandWithMockedDependency(): void
{
    $kernel = self::bootKernel();

    // Create mock
    $mailer = $this->createMock(MailerInterface::class);
    $mailer->expects($this->once())
        ->method('send')
        ->with(
            $this->equalTo('admin@example.com'),
            $this->stringContains('Import completed')
        );

    // Replace service in container
    self::getContainer()->set(MailerInterface::class, $mailer);

    $application = new Application($kernel);
    $command = $application->find('app:import');
    $commandTester = new CommandTester($command);

    $commandTester->execute(['filename' => 'test.csv']);

    $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
}
```

### Integration Tests

```php
public function testFullImportWorkflow(): void
{
    // Setup test data
    file_put_contents('/tmp/test_import.csv', "name,email\nJohn,john@test.com\nJane,jane@test.com");

    // Run import command
    $kernel = self::bootKernel();
    $application = new Application($kernel);

    $importCommand = $application->find('app:import');
    $importTester = new CommandTester($importCommand);

    $importTester->execute([
        'filename' => '/tmp/test_import.csv',
        '--format' => 'csv',
    ]);

    $this->assertSame(Command::SUCCESS, $importTester->getStatusCode());

    // Verify data was imported
    $users = $this->userRepository->findAll();
    $this->assertCount(2, $users);

    // Run export command
    $exportCommand = $application->find('app:export');
    $exportTester = new CommandTester($exportCommand);

    $exportTester->execute(['--format' => 'json']);

    $output = $exportTester->getDisplay();
    $data = json_decode($output, true);

    $this->assertCount(2, $data);
    $this->assertEquals('John', $data[0]['name']);

    // Cleanup
    unlink('/tmp/test_import.csv');
}
```

---

## Lazy Command Loading

### Why Lazy Loading?

Lazy loading improves performance by only instantiating commands when they're actually used, rather than loading all commands at application start.

### Implementing Lazy Commands

```php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;

// Option 1: Using #[AsCommand] attribute (automatically lazy)
#[AsCommand(
    name: 'app:heavy-task',
    description: 'A command with heavy dependencies'
)]
class HeavyTaskCommand extends Command
{
    public function __construct(
        private readonly ExpensiveService $expensiveService,
        private readonly AnotherHeavyService $anotherService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Command implementation
        return Command::SUCCESS;
    }
}
```

### Manual Lazy Command Registration

```php
// config/services.yaml
services:
    App\Command\HeavyTaskCommand:
        tags:
            - { name: 'console.command', command: 'app:heavy-task', lazy: true }
```

### Custom Lazy Command Factory

```php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;

class LazyCommandFactory
{
    public static function create(string $class, string $name, string $description): LazyCommand
    {
        return new LazyCommand(
            $name,
            [],
            $description,
            false,
            function () use ($class) {
                return new $class();
            }
        );
    }
}
```

---

## Signal Handling

### Basic Signal Handling

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:worker')]
class WorkerCommand extends Command implements SignalableCommandInterface
{
    private bool $shouldStop = false;
    private OutputInterface $output;

    public function getSubscribedSignals(): array
    {
        return [SIGTERM, SIGINT, SIGUSR1, SIGUSR2];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        $this->output->writeln(sprintf('Received signal: %d', $signal));

        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->output->writeln('Shutting down gracefully...');
                $this->shouldStop = true;
                return Command::SUCCESS;

            case SIGUSR1:
                $this->output->writeln('Reloading configuration...');
                $this->reloadConfiguration();
                return false; // Continue execution

            case SIGUSR2:
                $this->output->writeln('Printing statistics...');
                $this->printStatistics();
                return false; // Continue execution

            default:
                return false;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $output->writeln('Worker started. PID: ' . getmypid());
        $output->writeln('Send SIGUSR1 to reload, SIGUSR2 for stats, SIGTERM to stop');

        $iteration = 0;

        while (!$this->shouldStop) {
            $iteration++;
            $output->writeln(sprintf('Processing iteration %d...', $iteration), OutputInterface::VERBOSITY_VERBOSE);

            // Do work
            $this->processQueue();

            sleep(1);
        }

        $output->writeln('Worker stopped gracefully');

        return Command::SUCCESS;
    }

    private function processQueue(): void
    {
        // Process work here
    }

    private function reloadConfiguration(): void
    {
        // Reload configuration
    }

    private function printStatistics(): void
    {
        $this->output->writeln([
            'Statistics:',
            '  Memory: ' . memory_get_usage(true),
            '  Time: ' . date('H:i:s'),
        ]);
    }
}
```

### Graceful Shutdown Pattern

```php
#[AsCommand(name: 'app:long-running')]
class LongRunningCommand extends Command implements SignalableCommandInterface
{
    private bool $shouldStop = false;
    private array $activeJobs = [];

    public function getSubscribedSignals(): array
    {
        return [SIGTERM, SIGINT];
    }

    public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false
    {
        if ($signal === SIGTERM || $signal === SIGINT) {
            $this->shouldStop = true;

            // Wait for active jobs to complete
            $this->output->writeln('Waiting for active jobs to complete...');

            while (!empty($this->activeJobs)) {
                sleep(1);
            }

            return Command::SUCCESS;
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        while (!$this->shouldStop) {
            $job = $this->getNextJob();

            if ($job) {
                $this->activeJobs[] = $job->getId();
                $this->processJob($job);
                $this->activeJobs = array_filter($this->activeJobs, fn($id) => $id !== $job->getId());
            } else {
                sleep(1);
            }
        }

        return Command::SUCCESS;
    }
}
```

---

## Completion Scripts

### Enabling Shell Completion

```bash
# Bash
php bin/console completion bash | sudo tee /etc/bash_completion.d/console

# Zsh
php bin/console completion zsh | sudo tee /usr/local/share/zsh/site-functions/_console

# Fish
php bin/console completion fish > ~/.config/fish/completions/console.fish
```

### Custom Completion Logic

```php
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

#[AsCommand(name: 'app:user:show')]
class UserShowCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format')
        ;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        // Complete username argument
        if ($input->mustSuggestArgumentValuesFor('username')) {
            $usernames = $this->userRepository->findAllUsernames();
            $suggestions->suggestValues($usernames);
            return;
        }

        // Complete format option
        if ($input->mustSuggestOptionValuesFor('format')) {
            $suggestions->suggestValues(['json', 'xml', 'yaml', 'table']);
            return;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Command implementation
        return Command::SUCCESS;
    }
}
```

### Advanced Completion

```php
public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
{
    // Suggest values based on partial input
    if ($input->mustSuggestArgumentValuesFor('country')) {
        $countries = ['USA', 'UK', 'Germany', 'France', 'Spain'];

        // Filter based on current input
        $currentValue = $input->getArgument('country');
        if ($currentValue) {
            $countries = array_filter($countries, function($country) use ($currentValue) {
                return stripos($country, $currentValue) === 0;
            });
        }

        $suggestions->suggestValues($countries);
        return;
    }

    // Suggest file paths
    if ($input->mustSuggestArgumentValuesFor('file')) {
        // Symfony will automatically suggest file paths
        return;
    }

    // Dynamic suggestions based on other inputs
    if ($input->mustSuggestOptionValuesFor('role')) {
        $userType = $input->getOption('user-type');

        $roles = match($userType) {
            'admin' => ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'],
            'user' => ['ROLE_USER', 'ROLE_EDITOR'],
            default => ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_EDITOR'],
        };

        $suggestions->suggestValues($roles);
        return;
    }
}
```

---

## Custom Helpers

### Creating a Custom Helper

```php
namespace App\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Output\OutputInterface;

class SpinnerHelper extends Helper
{
    private const SPINNER_CHARS = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    private int $current = 0;

    public function getName(): string
    {
        return 'spinner';
    }

    public function start(OutputInterface $output, string $message): void
    {
        $output->write("\r" . self::SPINNER_CHARS[$this->current] . ' ' . $message);
        $this->current = ($this->current + 1) % count(self::SPINNER_CHARS);
    }

    public function stop(OutputInterface $output): void
    {
        $output->writeln("\r" . '✓ Done');
    }
}
```

### Using Custom Helper

```php
#[AsCommand(name: 'app:process')]
class ProcessCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $spinner = new SpinnerHelper();

        for ($i = 0; $i < 100; $i++) {
            $spinner->start($output, 'Processing...');
            usleep(50000);
        }

        $spinner->stop($output);

        return Command::SUCCESS;
    }
}
```

### Formatter Helper

```php
namespace App\Console\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class DataFormatterHelper extends Helper
{
    public function getName(): string
    {
        return 'data_formatter';
    }

    public function formatAsTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table
            ->setHeaders($headers)
            ->setRows($rows)
            ->setStyle('box')
        ;
        $table->render();
    }

    public function formatAsJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function formatAsCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
```

---

## Custom Output Formatters

### Creating Custom Output Styles

```php
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $formatter = $output->getFormatter();

    // Custom styles
    $formatter->setStyle('fire', new OutputFormatterStyle('red', 'yellow', ['bold', 'blink']));
    $formatter->setStyle('success', new OutputFormatterStyle('black', 'green', ['bold']));
    $formatter->setStyle('highlight', new OutputFormatterStyle('yellow', null, ['bold']));

    $output->writeln('<fire>CRITICAL WARNING!</fire>');
    $output->writeln('<success> SUCCESS </success>');
    $output->writeln('This is <highlight>important</highlight> text');

    return Command::SUCCESS;
}
```

### Custom Output Formatter Class

```php
namespace App\Console\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatter as BaseOutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class CustomOutputFormatter extends BaseOutputFormatter
{
    public function __construct(bool $decorated = false, array $styles = [])
    {
        parent::__construct($decorated, array_merge([
            'header' => new OutputFormatterStyle('white', 'blue', ['bold']),
            'warning' => new OutputFormatterStyle('black', 'yellow'),
            'critical' => new OutputFormatterStyle('white', 'red', ['bold', 'blink']),
            'debug' => new OutputFormatterStyle('cyan'),
        ], $styles));
    }

    public function formatSection(string $section, string $message): string
    {
        return $this->format(sprintf('<header>[ %s ]</header> %s', $section, $message));
    }
}
```

---

## Command Locking

### Preventing Concurrent Execution

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:import')]
class ImportCommand extends Command
{
    use LockableTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');
            return Command::FAILURE;
        }

        try {
            // Your command logic here
            $output->writeln('Processing...');
            sleep(5);
            $output->writeln('Done!');

            return Command::SUCCESS;
        } finally {
            $this->release();
        }
    }
}
```

### Custom Lock Names

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $userId = $input->getArgument('user-id');

    // Lock per user
    if (!$this->lock('app:import:user:' . $userId)) {
        $output->writeln("Import already running for user $userId");
        return Command::FAILURE;
    }

    try {
        // Process import for specific user
        return Command::SUCCESS;
    } finally {
        $this->release();
    }
}
```

---

## Performance Optimization

### Memory Management

```php
#[AsCommand(name: 'app:process-large-dataset')]
class ProcessLargeDatasetCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $batchSize = 100;
        $i = 0;

        $query = $this->entityManager->createQuery('SELECT u FROM App\Entity\User u');

        // Use iteration to avoid loading all results into memory
        foreach ($query->toIterable() as $user) {
            $this->processUser($user);

            if (++$i % $batchSize === 0) {
                // Flush and clear to free memory
                $this->entityManager->flush();
                $this->entityManager->clear();

                gc_collect_cycles();

                $io->writeln(sprintf('Processed %d users', $i), OutputInterface::VERBOSITY_VERBOSE);
            }
        }

        // Final flush
        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->success(sprintf('Processed %d users total', $i));

        return Command::SUCCESS;
    }
}
```

### Batch Processing

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);
    $batchSize = 1000;

    $totalCount = $this->repository->count([]);
    $batches = ceil($totalCount / $batchSize);

    $io->progressStart($batches);

    for ($i = 0; $i < $batches; $i++) {
        $offset = $i * $batchSize;

        $items = $this->repository->findBy(
            [],
            ['id' => 'ASC'],
            $batchSize,
            $offset
        );

        foreach ($items as $item) {
            $this->processItem($item);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $io->progressAdvance();
    }

    $io->progressFinish();

    return Command::SUCCESS;
}
```

---

## Advanced Patterns

### Command Factory Pattern

```php
namespace App\Command\Factory;

use Symfony\Component\Console\Command\Command;

interface CommandFactoryInterface
{
    public function create(array $config): Command;
}

class ImportCommandFactory implements CommandFactoryInterface
{
    public function __construct(
        private readonly ImporterInterface $importer,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(array $config): Command
    {
        return new ImportCommand(
            $this->importer,
            $this->logger,
            $config['source'],
            $config['format']
        );
    }
}
```

### Command Chain Pattern

```php
namespace App\Command;

interface ChainableCommandInterface
{
    public function supports(string $type): bool;
    public function process(InputInterface $input, OutputInterface $output): int;
}

class CommandChain
{
    private array $commands = [];

    public function addCommand(ChainableCommandInterface $command): void
    {
        $this->commands[] = $command;
    }

    public function execute(string $type, InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->commands as $command) {
            if ($command->supports($type)) {
                return $command->process($input, $output);
            }
        }

        throw new \RuntimeException("No command found for type: $type");
    }
}
```

### Command Template Method Pattern

```php
namespace App\Command;

abstract class AbstractImportCommand extends Command
{
    abstract protected function validateData(array $data): bool;
    abstract protected function transformData(array $data): array;
    abstract protected function saveData(array $data): void;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Template method pattern
        $rawData = $this->loadData($input);

        if (!$this->validateData($rawData)) {
            $io->error('Invalid data');
            return Command::FAILURE;
        }

        $transformedData = $this->transformData($rawData);
        $this->saveData($transformedData);

        $io->success('Import completed');

        return Command::SUCCESS;
    }

    private function loadData(InputInterface $input): array
    {
        // Common loading logic
        $filename = $input->getArgument('filename');
        return json_decode(file_get_contents($filename), true);
    }
}
```

### Retry Pattern with Exponential Backoff

```php
trait RetryableTrait
{
    private function executeWithRetry(
        callable $operation,
        int $maxAttempts = 3,
        int $baseDelay = 1000
    ): mixed {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    throw $e;
                }

                $delay = $baseDelay * (2 ** ($attempt - 1));
                $this->output->writeln(
                    sprintf('Attempt %d failed, retrying in %dms...', $attempt, $delay),
                    OutputInterface::VERBOSITY_VERBOSE
                );

                usleep($delay * 1000);
            }
        }
    }
}
```

---

## Best Practices Summary

### 1. Command Design
- Keep commands focused on a single responsibility
- Use dependency injection for all services
- Validate input early in `initialize()`
- Use `interact()` for interactive input collection

### 2. Output Management
- Use `SymfonyStyle` for consistent formatting
- Respect verbosity levels
- Provide helpful progress indicators for long operations
- Use section output for dynamic updates

### 3. Error Handling
- Always return appropriate exit codes
- Log errors for debugging
- Provide clear error messages
- Handle exceptions gracefully

### 4. Performance
- Use batch processing for large datasets
- Clear Doctrine EntityManager regularly
- Use iterators instead of loading all data at once
- Monitor memory usage

### 5. Testing
- Test all command paths (success, failure, edge cases)
- Test interactive modes with `setInputs()`
- Test different verbosity levels
- Use fixtures for consistent test data

### 6. Signal Handling
- Implement graceful shutdown for long-running commands
- Handle SIGTERM and SIGINT for clean termination
- Use SIGUSR1/SIGUSR2 for runtime control

### 7. Locking
- Use command locking for commands that shouldn't run concurrently
- Choose appropriate lock names
- Always release locks in `finally` blocks

### 8. Documentation
- Provide clear command descriptions
- Document all arguments and options
- Include usage examples in help text
- Keep documentation up to date
