# Quick Start Guide

Get started with the Console framework in 5 minutes.

## Installation

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/11-console

# Install dependencies
composer install

# Make console executable (Unix/Linux/Mac)
chmod +x bin/console
```

## Your First Command

### 1. Run a Simple Command

```bash
php bin/console app:greet World
```

Expected output:
```

 Greeting Command

==================


 Hello, World!


 [OK] Greeting completed successfully!

```

### 2. Try Different Options

```bash
# Uppercase
php bin/console app:greet World --uppercase

# Yell
php bin/console app:greet World --yell

# Combine options
php bin/console app:greet World --uppercase --yell

# Use short options
php bin/console app:greet World -u -y

# Multiple iterations
php bin/console app:greet World --iterations=3
```

### 3. List All Commands

```bash
php bin/console list
```

### 4. Get Help

```bash
php bin/console help app:greet
```

## Create Your Own Command

### Step 1: Create Command File

Create `src/Command/HelloCommand.php`:

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
    description: 'A simple hello command'
)]
class HelloCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Hello from my first command!</info>');
        return Command::SUCCESS;
    }
}
```

### Step 2: Register Command

Edit `bin/console`:

```php
use Command\HelloCommand;

// Register commands
$application->add(new HelloCommand());
```

### Step 3: Run Your Command

```bash
php bin/console app:hello
```

## More Examples

### Command with Arguments

```php
#[AsCommand(name: 'app:add', description: 'Add two numbers')]
class AddCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('a', InputArgument::REQUIRED, 'First number')
            ->addArgument('b', InputArgument::REQUIRED, 'Second number');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = (int) $input->getArgument('a');
        $b = (int) $input->getArgument('b');

        $output->writeln(sprintf('Result: %d', $a + $b));

        return Command::SUCCESS;
    }
}
```

Usage:
```bash
php bin/console app:add 5 3
# Output: Result: 8
```

### Command with SymfonyStyle

```php
use Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo', description: 'Demo styled output')]
class DemoCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('My Application');
        $io->section('Processing');

        $io->text('Doing something...');

        $io->success('Done!');

        return Command::SUCCESS;
    }
}
```

## Testing Your Commands

### Step 1: Create Test

Create `tests/Command/HelloCommandTest.php`:

```php
<?php

namespace Tests\Command;

use Command\HelloCommand;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;

class HelloCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new HelloCommand();
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Hello', $output->getContent());
    }
}
```

### Step 2: Run Tests

```bash
./vendor/bin/phpunit
```

## Available Commands

Try these commands to explore features:

```bash
# Simple greeting
php bin/console app:greet John

# List users with table
php bin/console app:list-users

# List users as JSON
php bin/console app:list-users --format=json

# Interactive demo
php bin/console app:interactive
```

## Next Steps

1. Read [README.md](README.md) for comprehensive documentation
2. Check [EXAMPLES.md](EXAMPLES.md) for more code examples
3. Study [HOW_IT_WORKS.md](HOW_IT_WORKS.md) to understand internals
4. Explore the source code in `src/Console/`
5. Create your own commands!

## Common Issues

### "Class not found" error

Run:
```bash
composer dump-autoload
```

### Permission denied

Make console executable:
```bash
chmod +x bin/console
```

### Colors not showing

Check if your terminal supports ANSI colors. On Windows, use:
- Windows Terminal
- ConEmu
- or enable ANSICON

## Resources

- PHP Documentation: https://www.php.net/
- Symfony Console Component: https://symfony.com/doc/current/components/console.html
- PSR-4 Autoloading: https://www.php-fig.org/psr/psr-4/

## Support

This is an educational project. For questions:
1. Read the documentation in this directory
2. Study the source code
3. Experiment with the examples
4. Compare with real Symfony Console component

Happy coding!
