# Chapter 24: Running Cron Jobs

Learn to schedule and automate tasks in Symfony using console commands and cron jobs.

---

## Learning Objectives

By the end of this chapter, you will:
- Create custom console commands
- Schedule tasks using Symfony Scheduler component
- Set up cron jobs on Linux/Unix systems
- Implement periodic cleanup and maintenance tasks
- Handle long-running processes and background jobs

---

## Prerequisites

- Completed Chapter 23 (Image Processing)
- Understanding of console commands
- Basic knowledge of Linux cron (optional)
- Familiarity with Symfony CLI

---

## Concepts

### What are Scheduled Tasks?

Scheduled tasks (cron jobs) are automated processes that run at specific times or intervals:

- **Maintenance**: Clean up old data, optimize database
- **Reports**: Generate daily/weekly reports
- **Notifications**: Send scheduled emails or reminders
- **Data Sync**: Import/export data from external systems
- **Cache Warmup**: Prepare cache before peak hours

### Scheduling Options

1. **Symfony Scheduler**: Modern, declarative approach (Symfony 6.3+)
2. **System Cron**: Traditional Unix/Linux cron daemon
3. **Supervisor**: Process control system for long-running tasks

---

## Step 1: Create a Console Command

Generate a command using the maker bundle:

```bash
php bin/console make:command app:cleanup-comments
```

This creates a new command class:

```php
// src/Command/CleanupCommentsCommand.php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-comments',
    description: 'Remove spam and rejected comments older than 30 days',
)]
class CleanupCommentsCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cleaning up old comments');

        // Cleanup logic will go here

        $io->success('Cleanup completed!');

        return Command::SUCCESS;
    }
}
```

---

## Step 2: Implement Cleanup Logic

Add the cleanup functionality:

```php
// src/Command/CleanupCommentsCommand.php
namespace App\Command;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-comments',
    description: 'Remove spam and rejected comments older than specified days',
)]
class CleanupCommentsCommand extends Command
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days', 30)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulate without deleting')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        $io->title(sprintf('Cleaning up comments older than %d days', $days));

        $cutoffDate = new \DateTime(sprintf('-%d days', $days));

        // Find old rejected/spam comments
        $comments = $this->commentRepository->createQueryBuilder('c')
            ->where('c.state IN (:states)')
            ->andWhere('c.createdAt < :cutoff')
            ->setParameter('states', ['spam', 'rejected'])
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->getResult();

        $count = count($comments);

        if ($count === 0) {
            $io->info('No comments to clean up.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d comment(s) to delete.', $count));

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No comments will be deleted');
            foreach ($comments as $comment) {
                $io->writeln(sprintf(
                    '- [%s] %s (created: %s)',
                    $comment->getState(),
                    substr($comment->getText(), 0, 50),
                    $comment->getCreatedAt()->format('Y-m-d H:i:s')
                ));
            }
        } else {
            $io->progressStart($count);

            foreach ($comments as $comment) {
                $this->em->remove($comment);
                $io->progressAdvance();
            }

            $this->em->flush();
            $io->progressFinish();

            $io->success(sprintf('Deleted %d comment(s).', $count));
        }

        return Command::SUCCESS;
    }
}
```

Test the command:

```bash
# Dry run to see what would be deleted
php bin/console app:cleanup-comments --dry-run

# Actually delete with custom days
php bin/console app:cleanup-comments --days=60

# Use default 30 days
php bin/console app:cleanup-comments
```

---

## Step 3: Use Symfony Scheduler (Recommended)

Install the Scheduler component:

```bash
composer require symfony/scheduler
```

Create a schedule provider:

```php
// src/Scheduler/TaskScheduleProvider.php
namespace App\Scheduler;

use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('default')]
class TaskScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            // Run cleanup daily at 3:00 AM
            ->add(
                RecurringMessage::cron('0 3 * * *', new Message\CleanupCommentsMessage())
            )
            // Run every hour
            ->add(
                RecurringMessage::every('1 hour', new Message\ProcessPendingCommentsMessage())
            )
            // Run every Monday at 9:00 AM
            ->add(
                RecurringMessage::cron('0 9 * * 1', new Message\WeeklyReportMessage())
            )
        ;
    }
}
```

Create message handlers:

```php
// src/Scheduler/Message/CleanupCommentsMessage.php
namespace App\Scheduler\Message;

class CleanupCommentsMessage
{
    public function __construct(
        public int $days = 30,
    ) {
    }
}
```

```php
// src/Scheduler/MessageHandler/CleanupCommentsHandler.php
namespace App\Scheduler\MessageHandler;

use App\Repository\CommentRepository;
use App\Scheduler\Message\CleanupCommentsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CleanupCommentsHandler
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CleanupCommentsMessage $message): void
    {
        $cutoffDate = new \DateTime(sprintf('-%d days', $message->days));

        $comments = $this->commentRepository->createQueryBuilder('c')
            ->where('c.state IN (:states)')
            ->andWhere('c.createdAt < :cutoff')
            ->setParameter('states', ['spam', 'rejected'])
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($comments as $comment) {
            $this->em->remove($comment);
            $count++;
        }

        $this->em->flush();

        $this->logger->info('Cleaned up {count} old comments', ['count' => $count]);
    }
}
```

Run the scheduler:

```bash
# Start the scheduler worker
php bin/console messenger:consume scheduler_default
```

---

## Step 4: Configure System Cron

For production, set up a system cron job to run the scheduler:

```bash
# Edit crontab
crontab -e
```

Add this line to run every minute:

```cron
* * * * * cd /path/to/project && php bin/console messenger:consume scheduler_default --time-limit=3600 >> /dev/null 2>&1
```

Or run specific commands directly:

```cron
# Cleanup comments daily at 3 AM
0 3 * * * cd /path/to/project && php bin/console app:cleanup-comments >> /var/log/cleanup.log 2>&1

# Process pending comments every hour
0 * * * * cd /path/to/project && php bin/console app:process-comments >> /var/log/process.log 2>&1

# Weekly report every Monday at 9 AM
0 9 * * 1 cd /path/to/project && php bin/console app:weekly-report >> /var/log/report.log 2>&1
```

---

## Step 5: Advanced Command Features

### Progress Bar

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $items = range(1, 100);
    $io->progressStart(count($items));

    foreach ($items as $item) {
        // Process item
        sleep(1);
        $io->progressAdvance();
    }

    $io->progressFinish();
    return Command::SUCCESS;
}
```

### Tables

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $comments = $this->commentRepository->findAll();

    $rows = [];
    foreach ($comments as $comment) {
        $rows[] = [
            $comment->getId(),
            $comment->getAuthor(),
            $comment->getState(),
            $comment->getCreatedAt()->format('Y-m-d H:i'),
        ];
    }

    $io->table(
        ['ID', 'Author', 'State', 'Created'],
        $rows
    );

    return Command::SUCCESS;
}
```

### Interactive Questions

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $io = new SymfonyStyle($input, $output);

    $days = $io->ask('How many days old?', 30);

    $confirm = $io->confirm('Delete all old comments?', false);

    if (!$confirm) {
        $io->warning('Operation cancelled.');
        return Command::SUCCESS;
    }

    $choice = $io->choice('Select action', ['delete', 'archive', 'export'], 'delete');

    return Command::SUCCESS;
}
```

---

## Step 6: Create Database Cleanup Command

```php
// src/Command/DatabaseMaintenanceCommand.php
namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:database:maintenance',
    description: 'Optimize database tables and clean up old data',
)]
class DatabaseMaintenanceCommand extends Command
{
    public function __construct(
        private Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Maintenance');

        // Clean up old sessions
        $io->section('Cleaning old sessions');
        $deleted = $this->connection->executeStatement(
            'DELETE FROM sessions WHERE sess_time < ?',
            [time() - 86400 * 30]
        );
        $io->success(sprintf('Deleted %d old sessions', $deleted));

        // Optimize tables
        $io->section('Optimizing tables');
        $tables = ['comment', 'conference', 'user'];

        foreach ($tables as $table) {
            $this->connection->executeStatement("OPTIMIZE TABLE $table");
            $io->writeln("✓ Optimized table: $table");
        }

        $io->success('Database maintenance completed!');
        return Command::SUCCESS;
    }
}
```

---

## Step 7: Email Report Command

```php
// src/Command/SendWeeklyReportCommand.php
namespace App\Command;

use App\Repository\CommentRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:report:weekly',
    description: 'Send weekly statistics report',
)]
class SendWeeklyReportCommand extends Command
{
    public function __construct(
        private CommentRepository $commentRepository,
        private MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Calculate statistics
        $stats = [
            'total' => $this->commentRepository->count([]),
            'pending' => $this->commentRepository->count(['state' => 'submitted']),
            'approved' => $this->commentRepository->count(['state' => 'published']),
            'spam' => $this->commentRepository->count(['state' => 'spam']),
        ];

        // Get recent comments
        $recentComments = $this->commentRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        // Send email
        $email = (new TemplatedEmail())
            ->to('admin@example.com')
            ->subject('Weekly Comment Report')
            ->htmlTemplate('emails/weekly_report.html.twig')
            ->context([
                'stats' => $stats,
                'recentComments' => $recentComments,
            ]);

        $this->mailer->send($email);

        $io->success('Weekly report sent!');
        return Command::SUCCESS;
    }
}
```

---

## Step 8: Using Supervisor for Long-Running Tasks

Install Supervisor:

```bash
sudo apt-get install supervisor
```

Create a configuration file:

```ini
; /etc/supervisor/conf.d/symfony-messenger.conf
[program:symfony-messenger]
command=php /path/to/project/bin/console messenger:consume async --time-limit=3600
user=www-data
numprocs=2
startsecs=0
autostart=true
autorestart=true
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/log/symfony-messenger.out.log
stderr_logfile=/var/log/symfony-messenger.err.log
```

Manage the process:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start symfony-messenger:*
sudo supervisorctl status
```

---

## Step 9: Monitoring and Logging

Add logging to your commands:

```php
use Psr\Log\LoggerInterface;

class CleanupCommentsCommand extends Command
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting comment cleanup');

        try {
            // Cleanup logic
            $count = 42; // number deleted

            $this->logger->info('Cleanup completed', [
                'deleted' => $count,
                'days' => $days,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Cleanup failed', [
                'error' => $e->getMessage(),
            ]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
```

---

## Step 10: Common Scheduled Tasks

### Cache Warmup

```php
#[AsCommand(name: 'app:cache:warmup-important')]
class CacheWarmupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Warm up specific cache pools before peak hours
        $this->cache->get('homepage_data', function() {
            return $this->generateHomepageData();
        });

        return Command::SUCCESS;
    }
}
```

### Sitemap Generation

```php
#[AsCommand(name: 'app:sitemap:generate')]
class GenerateSitemapCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Generate sitemap.xml
        $sitemap = $this->sitemapGenerator->generate();
        file_put_contents('public/sitemap.xml', $sitemap);

        return Command::SUCCESS;
    }
}
```

---

## Key Concepts Covered

1. **Console Commands**: Creating custom CLI commands
2. **Symfony Scheduler**: Modern task scheduling with messages
3. **Cron Jobs**: Traditional Unix/Linux scheduling
4. **Command Options**: Configurable parameters and flags
5. **Progress Indicators**: User-friendly feedback
6. **Error Handling**: Logging and monitoring
7. **Supervisor**: Process management for long-running tasks
8. **Best Practices**: Dry-run mode, logging, documentation

---

## Exercises

### Exercise 1: Create Backup Command

Create a command that backs up the database:

```php
#[AsCommand(name: 'app:database:backup')]
class DatabaseBackupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filename = sprintf('backup_%s.sql', date('Y-m-d_H-i-s'));
        $backupPath = '/path/to/backups/' . $filename;

        // Execute mysqldump
        $command = sprintf(
            'mysqldump -u%s -p%s %s > %s',
            'username',
            'password',
            'database',
            $backupPath
        );

        exec($command);

        $io->success('Database backed up to: ' . $filename);
        return Command::SUCCESS;
    }
}
```

Schedule it daily at 2 AM.

### Exercise 2: User Activity Report

Create a command that generates a monthly user activity report.

### Exercise 3: Expired Content Cleanup

Create a command that archives or deletes expired content based on a timestamp field.

---

## Questions

1. What is the difference between Symfony Scheduler and system cron?
2. How do you make a command option required?
3. What does `Command::SUCCESS` return value indicate?
4. How do you run a command in dry-run mode?
5. What is Supervisor used for?

### Answers

1. Symfony Scheduler is integrated with the Messenger component and provides a modern, PHP-based approach. System cron is OS-level scheduling that's more traditional but works independently of your application.

2. Use `InputOption::VALUE_REQUIRED` instead of `InputOption::VALUE_OPTIONAL`

3. It indicates the command executed successfully (returns 0). Use `Command::FAILURE` for errors (returns 1).

4. Add a `--dry-run` option to your command and check for it: `if ($input->getOption('dry-run')) { ... }`

5. Supervisor is a process control system that keeps long-running processes (like messenger consumers) running continuously, automatically restarting them if they crash.

---

## Next Step

Proceed to [Chapter 25: Notifying by All Means](../25-notifier/README.md) to learn about the Notifier component for multi-channel notifications.
