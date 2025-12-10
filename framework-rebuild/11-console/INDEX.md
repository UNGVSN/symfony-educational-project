# Chapter 11: Console - Complete Index

Welcome to Chapter 11 of the Symfony Framework Rebuild educational project. This chapter teaches you how to build CLI applications using a Symfony Console-like framework.

## Directory Structure

```
11-console/
├── bin/
│   └── console                    # Entry point for CLI app
├── src/
│   ├── Console/                   # Core console framework
│   │   ├── Application.php        # Main application class
│   │   ├── Command/
│   │   │   ├── Command.php        # Base command class
│   │   │   └── AsCommand.php      # Command attribute
│   │   ├── Input/
│   │   │   ├── InputInterface.php # Input abstraction
│   │   │   ├── ArgvInput.php      # CLI argument parser
│   │   │   ├── InputArgument.php  # Argument definition
│   │   │   └── InputOption.php    # Option definition
│   │   ├── Output/
│   │   │   ├── OutputInterface.php # Output abstraction
│   │   │   └── ConsoleOutput.php  # Console output with colors
│   │   ├── Style/
│   │   │   └── SymfonyStyle.php   # High-level output API
│   │   └── Helper/
│   │       ├── ProgressBar.php    # Progress bar helper
│   │       └── Table.php          # Table helper
│   └── Command/                   # Example commands
│       ├── GreetCommand.php       # Simple greeting command
│       ├── ListUsersCommand.php   # Table & progress demo
│       └── InteractiveCommand.php # Interactive features demo
├── tests/                         # Test suite
│   ├── Console/
│   │   ├── Input/
│   │   │   ├── ArrayInput.php     # Test input class
│   │   │   ├── InputArgumentTest.php
│   │   │   └── InputOptionTest.php
│   │   ├── Output/
│   │   │   └── BufferedOutput.php # Test output class
│   │   └── ApplicationTest.php
│   └── Command/
│       └── GreetCommandTest.php
├── README.md                      # Comprehensive documentation
├── QUICK_START.md                 # 5-minute getting started
├── EXAMPLES.md                    # Code examples & usage
├── HOW_IT_WORKS.md                # Internal architecture
├── composer.json                  # Dependencies & autoloading
├── phpunit.xml                    # PHPUnit configuration
└── .gitignore                     # Git ignore rules
```

## Documentation Guide

### For Beginners

Start here if you're new to CLI applications:

1. **[QUICK_START.md](QUICK_START.md)** - Get up and running in 5 minutes
   - Installation
   - Running your first command
   - Creating a simple command
   - Basic examples

2. **[README.md](README.md)** - Complete documentation
   - Introduction to CLI applications
   - Command structure
   - Input/Output handling
   - Helpers and styling
   - Best practices

3. **[EXAMPLES.md](EXAMPLES.md)** - Practical code examples
   - Running commands
   - Command options and arguments
   - Creating custom commands
   - Progress bars and tables
   - Interactive features

### For Advanced Users

Deep dive into the framework:

4. **[HOW_IT_WORKS.md](HOW_IT_WORKS.md)** - Internal architecture
   - Architecture overview
   - Component breakdown
   - Data flow
   - Design patterns
   - Extension points

### Source Code

Study the implementation:

5. **Core Framework** (`src/Console/`)
   - Application.php - Command registry and execution
   - Command/Command.php - Base command class
   - Input/ArgvInput.php - Argument parser
   - Output/ConsoleOutput.php - Formatted output
   - Style/SymfonyStyle.php - High-level API
   - Helper/ProgressBar.php - Progress indicator
   - Helper/Table.php - Table renderer

6. **Example Commands** (`src/Command/`)
   - GreetCommand.php - Arguments & options
   - ListUsersCommand.php - Tables & progress bars
   - InteractiveCommand.php - Questions & choices

7. **Tests** (`tests/`)
   - Unit tests for all components
   - Integration tests for commands
   - Test utilities (ArrayInput, BufferedOutput)

## Learning Path

### Level 1: Basics (30 minutes)

1. Read [QUICK_START.md](QUICK_START.md)
2. Run the example commands:
   ```bash
   php bin/console list
   php bin/console app:greet World --uppercase
   php bin/console app:list-users
   ```
3. Create your first command following the guide

### Level 2: Intermediate (1-2 hours)

1. Read [README.md](README.md) sections:
   - Command Structure
   - Input/Output Handling
   - Helpers
2. Read [EXAMPLES.md](EXAMPLES.md)
3. Study the example commands in `src/Command/`
4. Create commands with:
   - Arguments and options
   - Progress bars
   - Tables
   - Interactive questions

### Level 3: Advanced (2-4 hours)

1. Read [HOW_IT_WORKS.md](HOW_IT_WORKS.md)
2. Study the framework source code:
   - Application lifecycle
   - Input parsing algorithm
   - Output formatting
   - Helper implementations
3. Understand design patterns used
4. Write comprehensive tests

### Level 4: Expert (4+ hours)

1. Implement custom features:
   - Custom Input class
   - Custom Output formatter
   - New helpers
   - Custom styles
2. Compare with real Symfony Console
3. Extend the framework
4. Contribute improvements

## Key Concepts

### Essential

- **Application**: Entry point, command registry
- **Command**: Encapsulates a CLI action
- **Input**: Parses and provides arguments/options
- **Output**: Writes formatted text to console
- **Arguments**: Positional parameters (required order)
- **Options**: Named parameters (any order)

### Intermediate

- **SymfonyStyle**: High-level output API
- **ProgressBar**: Visual feedback for long operations
- **Table**: Tabular data rendering
- **Verbosity Levels**: Control output detail
- **Exit Codes**: Indicate success/failure
- **Command Lifecycle**: initialize → interact → execute

### Advanced

- **Input Binding**: Map definitions to values
- **ANSI Formatting**: Color codes and styles
- **Command Attributes**: #[AsCommand] decorator
- **Input Validation**: Check required parameters
- **Interactive Mode**: Questions and confirmations
- **Helper System**: Reusable output components

## Quick Reference

### Command Template

```php
<?php
declare(strict_types=1);

namespace Command;

use Console\Command\{AsCommand, Command};
use Console\Input\{InputInterface, InputArgument, InputOption};
use Console\Output\OutputInterface;
use Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:my-command',
    description: 'Command description'
)]
class MyCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Description')
            ->addOption('option', 'o', InputOption::VALUE_NONE, 'Description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Your logic here

        $io->success('Done!');
        return Command::SUCCESS;
    }
}
```

### Common Commands

```bash
# List commands
php bin/console list

# Get help
php bin/console help command-name

# Run command
php bin/console command-name [arguments] [options]

# Verbosity
php bin/console command-name -q      # Quiet
php bin/console command-name -v      # Verbose
php bin/console command-name -vv     # Very verbose
php bin/console command-name -vvv    # Debug
```

### Testing Template

```php
<?php
namespace Tests\Command;

use PHPUnit\Framework\TestCase;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;

class MyCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new MyCommand();
        $input = new ArrayInput(['name' => 'value']);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('expected', $output->getContent());
    }
}
```

## File Index

### Documentation Files

| File | Purpose | Time to Read |
|------|---------|--------------|
| [README.md](README.md) | Main documentation | 30 min |
| [QUICK_START.md](QUICK_START.md) | Getting started | 5 min |
| [EXAMPLES.md](EXAMPLES.md) | Code examples | 20 min |
| [HOW_IT_WORKS.md](HOW_IT_WORKS.md) | Architecture | 45 min |
| INDEX.md | This file | 10 min |

### Core Framework Files

| File | Lines | Purpose |
|------|-------|---------|
| Application.php | ~200 | Command registry & execution |
| Command/Command.php | ~300 | Base command class |
| Command/AsCommand.php | ~20 | PHP 8 attribute |
| Input/InputInterface.php | ~50 | Input abstraction |
| Input/ArgvInput.php | ~250 | CLI parser |
| Input/InputArgument.php | ~80 | Argument definition |
| Input/InputOption.php | ~100 | Option definition |
| Output/OutputInterface.php | ~60 | Output abstraction |
| Output/ConsoleOutput.php | ~200 | Console output |
| Style/SymfonyStyle.php | ~300 | High-level API |
| Helper/ProgressBar.php | ~200 | Progress bar |
| Helper/Table.php | ~250 | Table renderer |

### Example Command Files

| File | Lines | Demonstrates |
|------|-------|--------------|
| GreetCommand.php | ~100 | Arguments, options |
| ListUsersCommand.php | ~150 | Tables, progress bars |
| InteractiveCommand.php | ~100 | Questions, choices |

### Test Files

| File | Purpose |
|------|---------|
| ApplicationTest.php | Test application |
| GreetCommandTest.php | Test command execution |
| InputArgumentTest.php | Test argument validation |
| InputOptionTest.php | Test option validation |
| ArrayInput.php | Test input utility |
| BufferedOutput.php | Test output utility |

## Prerequisites

- PHP 8.2 or higher
- Composer
- Basic understanding of:
  - Object-oriented PHP
  - Command-line interfaces
  - Namespaces and autoloading

## Installation

```bash
cd 11-console
composer install
chmod +x bin/console
```

## Running Examples

```bash
# List all commands
php bin/console list

# Simple greeting
php bin/console app:greet World

# With options
php bin/console app:greet John --uppercase --yell

# List users
php bin/console app:list-users

# Different format
php bin/console app:list-users --format=json

# Interactive demo
php bin/console app:interactive
```

## Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific test
./vendor/bin/phpunit tests/Command/GreetCommandTest.php

# With coverage (requires Xdebug)
./vendor/bin/phpunit --coverage-html coverage
```

## Next Steps After This Chapter

After mastering this chapter, you'll be ready to:

1. Build production-grade CLI tools
2. Automate repetitive tasks
3. Create developer utilities
4. Build deployment scripts
5. Implement cron jobs
6. Create database seeders
7. Build migration tools

## Related Symfony Components

This chapter is a simplified version of:
- **symfony/console** - The real Symfony Console component

Related components to study next:
- **symfony/process** - Run external processes
- **symfony/filesystem** - File operations
- **symfony/finder** - File/directory search
- **symfony/dotenv** - Environment variables
- **symfony/lock** - Prevent concurrent execution

## Additional Resources

- [Symfony Console Documentation](https://symfony.com/doc/current/components/console.html)
- [PHP CLI Documentation](https://www.php.net/manual/en/features.commandline.php)
- [ANSI Escape Codes](https://en.wikipedia.org/wiki/ANSI_escape_code)
- [Command Design Pattern](https://refactoring.guru/design-patterns/command)

## Contributing

This is an educational project. Feel free to:
- Add more example commands
- Improve documentation
- Add more tests
- Extend functionality
- Fix bugs

## License

Educational use only. Part of the Symfony Framework Rebuild project.

---

**Ready to start? Go to [QUICK_START.md](QUICK_START.md)!**
