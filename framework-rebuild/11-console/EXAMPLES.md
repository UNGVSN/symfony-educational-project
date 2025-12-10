# Console Examples

This document provides practical examples of using the Console framework.

## Installation

```bash
# Install dependencies
composer install

# Make console executable
chmod +x bin/console
```

## Running Commands

### List Available Commands

```bash
php bin/console list
```

Output:
```
Educational Console App version 1.0.0

Usage:
  command [options] [arguments]

Available commands:
 app
  app:greet                      Greets a user
  app:list-users                 Lists all users
```

### Get Help for a Command

```bash
php bin/console help app:greet
```

Output:
```
Description:
  Greets a user

Usage:
  app:greet [options] [--] <name> [<last-name>]

Arguments:
  name                  Who do you want to greet?
  last-name             Last name (optional)

Options:
  -u, --uppercase       Uppercase the greeting
  -y, --yell            Add exclamation marks
  -i, --iterations      Number of times to greet [default: 1]

Help:
  This command allows you to greet a user with various formatting options.
```

## Example 1: Basic Greeting

### Simple Greeting

```bash
php bin/console app:greet John
```

Output:
```
 Educational Console App

========================

 Hello, John!


 [OK] Greeting completed successfully!
```

### With Last Name

```bash
php bin/console app:greet John Doe
```

Output:
```
 Hello, John Doe!
```

### Uppercase Greeting

```bash
php bin/console app:greet John --uppercase
# or
php bin/console app:greet John -u
```

Output:
```
 HELLO, JOHN!
```

### Yelling Greeting

```bash
php bin/console app:greet John --yell
# or
php bin/console app:greet John -y
```

Output:
```
 Hello, John!!!
```

### Combined Options

```bash
php bin/console app:greet John --uppercase --yell
```

Output:
```
 HELLO, JOHN!!!
```

### Multiple Iterations

```bash
php bin/console app:greet World --iterations=3
# or
php bin/console app:greet World -i3
```

Output:
```
 Hello, World!
 Hello, World!
 Hello, World!
```

## Example 2: List Users

### Default Table Format

```bash
php bin/console app:list-users
```

Output:
```
 User List
===========

Fetching users from database...
---------------------------------

 10/10 [============================] 100%  00:01/00:01 8.0MB

Users Table
-----------

+----+----------------+---------------------+-----------+
| ID | Name           | Email               | Role      |
+----+----------------+---------------------+-----------+
| 1  | John Doe       | john@example.com    | Admin     |
| 2  | Jane Smith     | jane@example.com    | User      |
| 3  | Bob Johnson    | bob@example.com     | User      |
| 4  | Alice Williams | alice@example.com   | Moderator |
| 5  | Charlie Brown  | charlie@example.com | User      |
+----+----------------+---------------------+-----------+


 [OK] Successfully displayed 5 users
```

### JSON Format

```bash
php bin/console app:list-users --format=json
# or
php bin/console app:list-users -f json
```

Output:
```json
[
    {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "Admin"
    },
    {
        "id": 2,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "role": "User"
    }
]
```

### CSV Format

```bash
php bin/console app:list-users --format=csv
# or
php bin/console app:list-users -f csv
```

Output:
```csv
ID,Name,Email,Role
1,"John Doe","john@example.com","Admin"
2,"Jane Smith","jane@example.com","User"
```

### Limit Results

```bash
php bin/console app:list-users --limit=5
# or
php bin/console app:list-users -l5
```

## Creating Your Own Commands

### Simple Command

Create a new file `src/Command/HelloCommand.php`:

```php
<?php

declare(strict_types=1);

namespace Command;

use Console\Command\AsCommand;
use Console\Command\Command;
use Console\Input\InputInterface;
use Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:hello',
    description: 'Says hello'
)]
class HelloCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Hello, World!</info>');
        return Command::SUCCESS;
    }
}
```

Register it in `bin/console`:

```php
$application->add(new HelloCommand());
```

Run it:

```bash
php bin/console app:hello
```

### Command with Arguments

```php
#[AsCommand(
    name: 'app:calculate',
    description: 'Calculate sum of numbers'
)]
class CalculateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('numbers', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Numbers to sum');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numbers = $input->getArgument('numbers');
        $sum = array_sum($numbers);

        $output->writeln(sprintf('Sum: %d', $sum));

        return Command::SUCCESS;
    }
}
```

Usage:

```bash
php bin/console app:calculate 1 2 3 4 5
# Output: Sum: 15
```

### Command with Interactive Questions

```php
use Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:register',
    description: 'Register a new user'
)]
class RegisterCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('User Registration');

        // Ask questions
        $name = $io->ask('What is your name?');
        $email = $io->ask('What is your email?');
        $password = $io->askHidden('Enter password');
        $confirmed = $io->confirm('Create user?', true);

        if ($confirmed) {
            // Create user...
            $io->success(sprintf('User "%s" created successfully!', $name));
        } else {
            $io->warning('User creation cancelled');
        }

        return Command::SUCCESS;
    }
}
```

### Command with Progress Bar

```php
use Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process',
    description: 'Process items'
)]
class ProcessCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $items = range(1, 100);

        $io->title('Processing Items');
        $io->progressStart(count($items));

        foreach ($items as $item) {
            // Process item
            usleep(50000); // Sleep 0.05 seconds

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('All items processed!');

        return Command::SUCCESS;
    }
}
```

### Command with Table Output

```php
use Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:report',
    description: 'Generate a report'
)]
class ReportCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Monthly Report');

        $data = [
            ['January', 100, 80],
            ['February', 120, 95],
            ['March', 90, 75],
        ];

        $io->table(
            ['Month', 'Sales', 'Revenue'],
            $data
        );

        return Command::SUCCESS;
    }
}
```

## Advanced Usage

### Verbosity Levels

```bash
# Quiet (no output)
php bin/console app:greet John -q

# Normal (default)
php bin/console app:greet John

# Verbose
php bin/console app:greet John -v

# Very verbose
php bin/console app:greet John -vv

# Debug
php bin/console app:greet John -vvv
```

In your command:

```php
if ($output->isVerbose()) {
    $output->writeln('Verbose message');
}

if ($output->isDebug()) {
    $output->writeln('Debug information');
}
```

### Exit Codes

```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    try {
        // Do something
        return Command::SUCCESS; // 0
    } catch (ValidationException $e) {
        $output->writeln('<error>Invalid input</error>');
        return Command::INVALID; // 2
    } catch (\Exception $e) {
        $output->writeln('<error>An error occurred</error>');
        return Command::FAILURE; // 1
    }
}
```

Check exit code in shell:

```bash
php bin/console app:greet John
echo $?  # Prints: 0
```

### Color and Formatting

```php
$output->writeln('<info>Info message (green)</info>');
$output->writeln('<comment>Comment message (yellow)</comment>');
$output->writeln('<question>Question message (cyan)</question>');
$output->writeln('<error>Error message (white on red)</error>');
$output->writeln('<fg=red>Red text</>');
$output->writeln('<bg=blue>Blue background</>');
$output->writeln('<bold>Bold text</>');
$output->writeln('<underline>Underlined text</>');
```

## Testing Commands

Example test:

```php
use PHPUnit\Framework\TestCase;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;

class MyCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new MyCommand();

        $input = new ArrayInput([
            'name' => 'John',
            '--option' => true,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('expected text', $output->getContent());
    }
}
```

Run tests:

```bash
composer install
./vendor/bin/phpunit
```

## Best Practices

1. **Use meaningful command names**: Follow the `namespace:action` pattern (e.g., `cache:clear`, `user:create`)
2. **Provide good help text**: Always add descriptions for arguments and options
3. **Return proper exit codes**: Use `Command::SUCCESS`, `Command::FAILURE`, or `Command::INVALID`
4. **Validate input**: Check arguments and options before processing
5. **Use SymfonyStyle**: Provides consistent, beautiful output
6. **Add progress indicators**: For long-running operations
7. **Ask for confirmation**: Before destructive operations
8. **Support different output formats**: JSON, CSV, XML for data commands
9. **Make commands testable**: Use dependency injection
10. **Document your commands**: Add examples to help text
