# How Symfony Console Works

This document explains the internal workings of the Symfony Console component.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                       User Input                             │
│         php bin/console app:greet John --uppercase           │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      Application                             │
│  - Entry point                                               │
│  - Command registry                                          │
│  - Input/Output initialization                               │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                     ArgvInput                                │
│  - Parse $_SERVER['argv']                                    │
│  - Extract command name: "app:greet"                         │
│  - Extract arguments: ["name" => "John"]                     │
│  - Extract options: ["uppercase" => true]                    │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                  Command Discovery                           │
│  - Application::find("app:greet")                            │
│  - Returns: GreetCommand instance                            │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   Command Execution                          │
│  ┌────────────────────────────────────────────────┐         │
│  │ 1. Command::run()                              │         │
│  │    ├─ bind input definitions                  │         │
│  │    ├─ validate input                          │         │
│  │    ├─ initialize()                            │         │
│  │    ├─ interact()   (if interactive)           │         │
│  │    └─ execute()                               │         │
│  └────────────────────────────────────────────────┘         │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                   ConsoleOutput                              │
│  - Format text with ANSI codes                               │
│  - Apply colors and styles                                   │
│  - Write to STDOUT/STDERR                                    │
└─────────────────────────┬───────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      Terminal                                │
│                   Hello, JOHN!                               │
└─────────────────────────────────────────────────────────────┘
```

## Component Breakdown

### 1. Application Class

**Purpose**: Central hub for command management and execution.

**Responsibilities**:
- Register commands
- Parse initial input to get command name
- Find and execute the requested command
- Handle exceptions and errors
- Display help and list commands

**Key Methods**:
```php
class Application
{
    // Register a command
    public function add(Command $command): Command

    // Find a command by name
    public function find(string $name): Command

    // Execute the application
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
}
```

**Lifecycle**:
```
Application::run()
    │
    ├─ Create Input (ArgvInput or custom)
    ├─ Create Output (ConsoleOutput or custom)
    │
    ├─ Get command name from input
    │
    ├─ Find command in registry
    │   └─ Throw exception if not found
    │
    ├─ Execute command
    │   └─ Command::run()
    │
    └─ Return exit code
```

### 2. Input System

#### InputInterface

**Purpose**: Abstract access to command-line arguments and options.

**Implementations**:
- `ArgvInput`: Parses `$_SERVER['argv']`
- `ArrayInput`: For testing with array data
- `StringInput`: Parses a string (not implemented in this example)

#### ArgvInput Parsing

**Input Format**:
```
php bin/console command:name arg1 arg2 --opt1 --opt2=value -abc
                     │        │    │     │       │           │
                  command  args  args options options    flags
```

**Parsing Algorithm**:
```php
foreach ($tokens as $token) {
    if ($token === '--') {
        // Stop parsing options
        $parseOptions = false;
    }
    elseif (starts_with($token, '--')) {
        // Long option: --name or --name=value
        parse_long_option($token);
    }
    elseif (starts_with($token, '-')) {
        // Short option: -v or -vvv
        parse_short_option($token);
    }
    else {
        // Argument
        add_argument($token);
    }
}
```

**Example**:
```bash
php bin/console app:greet John Doe --uppercase -y --iterations=3
```

Parsed as:
```php
[
    'arguments' => [
        'name' => 'John',
        'last-name' => 'Doe'
    ],
    'options' => [
        'uppercase' => true,
        'yell' => true,
        'iterations' => '3'
    ]
]
```

#### InputArgument

**Purpose**: Define and validate command arguments (positional parameters).

**Modes**:
- `REQUIRED`: Must be provided
- `OPTIONAL`: Can be omitted
- `IS_ARRAY`: Accepts multiple values (must be last)

**Example**:
```php
$command->addArgument('name', InputArgument::REQUIRED, 'User name');
$command->addArgument('tags', InputArgument::IS_ARRAY, 'Tags');

// Usage: php bin/console cmd John tag1 tag2 tag3
// Result: ['name' => 'John', 'tags' => ['tag1', 'tag2', 'tag3']]
```

#### InputOption

**Purpose**: Define and validate command options (named parameters).

**Modes**:
- `VALUE_NONE`: Flag option (--verbose)
- `VALUE_REQUIRED`: Must have value (--env=prod)
- `VALUE_OPTIONAL`: Optional value (--iterations[=5])
- `VALUE_IS_ARRAY`: Multiple values (--exclude=*.log --exclude=*.tmp)

**Example**:
```php
$command->addOption('verbose', 'v', InputOption::VALUE_NONE);
$command->addOption('env', null, InputOption::VALUE_REQUIRED);

// Usage: php bin/console cmd --verbose --env=prod
// Or:    php bin/console cmd -v --env=prod
```

### 3. Output System

#### OutputInterface

**Purpose**: Abstract output operations with verbosity and formatting support.

**Verbosity Levels**:
```php
VERBOSITY_QUIET         = 16   // -q, --quiet
VERBOSITY_NORMAL        = 32   // default
VERBOSITY_VERBOSE       = 64   // -v
VERBOSITY_VERY_VERBOSE  = 128  // -vv
VERBOSITY_DEBUG         = 256  // -vvv
```

**Usage**:
```php
// Always shown
$output->writeln('Normal message');

// Only in verbose mode
if ($output->isVerbose()) {
    $output->writeln('Verbose message');
}

// Only in debug mode
if ($output->isDebug()) {
    $output->writeln('Debug information');
}
```

#### ConsoleOutput

**Purpose**: Write formatted text to console with ANSI color support.

**Formatting Tags**:
```php
// Predefined styles
<info>Green text</info>
<comment>Yellow text</comment>
<question>Cyan text</question>
<error>White on red background</error>

// Custom colors
<fg=red>Red foreground</>
<bg=blue>Blue background</>

// Text styles
<bold>Bold text</>
<underline>Underlined text</>
```

**ANSI Code Conversion**:
```php
Input:  "<info>Hello</info>"
Output: "\033[32mHello\033[0m"
         │      │    │      │
         │      │    │      └─ Reset
         │      │    └─ Text
         │      └─ Green color code
         └─ ANSI escape sequence
```

**Color Support Detection**:
```php
// Unix/Linux/Mac
function hasColorSupport(): bool {
    return posix_isatty(STDOUT);
}

// Windows
function hasColorSupport(): bool {
    return getenv('ANSICON') !== false
        || getenv('ConEmuANSI') === 'ON';
}
```

### 4. Command System

#### Command Lifecycle

```
Command::run()
    │
    ├─ 1. bind($input)
    │   └─ Bind argument/option definitions to input
    │
    ├─ 2. validate($input)
    │   └─ Check required arguments are present
    │
    ├─ 3. initialize($input, $output)
    │   └─ Optional setup (override in subclass)
    │
    ├─ 4. interact($input, $output)
    │   └─ Ask questions for missing input (override in subclass)
    │   └─ Only called if input is interactive
    │
    ├─ 5. execute($input, $output)
    │   └─ Main command logic (required in subclass)
    │
    └─ Return exit code (0 = success, 1 = failure, 2 = invalid)
```

#### Command Configuration

**Using Attributes** (PHP 8+):
```php
#[AsCommand(
    name: 'app:greet',
    description: 'Greets a user',
    hidden: false,
    aliases: ['greet']
)]
class GreetCommand extends Command
{
    // No need to set name/description in configure()
}
```

**Using configure() Method**:
```php
class GreetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('app:greet')
            ->setDescription('Greets a user')
            ->setHelp('This command greets a user...')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE);
    }
}
```

### 5. Helper System

#### ProgressBar

**Purpose**: Visual feedback for long-running operations.

**Implementation**:
```php
class ProgressBar
{
    private int $current = 0;
    private int $max;

    public function start(): void {
        // Display initial bar: [----------] 0%
    }

    public function advance(int $step = 1): void {
        $this->current += $step;
        $this->display(); // Redraw bar
    }

    private function display(): void {
        // Calculate percentage
        $percent = $this->current / $this->max;

        // Calculate bar
        $complete = floor($percent * $barWidth);
        $empty = $barWidth - $complete;

        // Render: \r [=====-----] 50% 00:05/00:10
        $output->write("\r" . $this->generate());
    }
}
```

**Output**:
```
 10/100 [====>-----------------------] 10%  00:01/00:10 8.0MB
           │    │                      │      │      │    │
         complete│                   percent  │   estimate│
              progress char              elapsed    memory
```

#### Table

**Purpose**: Render tabular data.

**Implementation**:
```php
class Table
{
    public function render(): void {
        // 1. Calculate column widths
        $widths = $this->calculateColumnWidths();

        // 2. Render top border
        // +----+------+-------+

        // 3. Render header
        // | ID | Name | Email |

        // 4. Render separator
        // +----+------+-------+

        // 5. Render rows
        // | 1  | John | ...   |

        // 6. Render bottom border
        // +----+------+-------+
    }
}
```

#### SymfonyStyle

**Purpose**: High-level API for consistent, beautiful output.

**Implementation**: Wrapper around Output + Helpers
```php
class SymfonyStyle
{
    private InputInterface $input;
    private OutputInterface $output;

    public function title(string $message): void {
        $this->output->writeln(sprintf('<bg=blue> %s </>', $message));
        $this->output->writeln(str_repeat('=', strlen($message)));
    }

    public function success(string $message): void {
        $this->block($message, 'OK', 'success');
    }

    public function table(array $headers, array $rows): void {
        $table = new Table($this->output);
        $table->setHeaders($headers)->setRows($rows)->render();
    }
}
```

## Data Flow Example

Let's trace a complete command execution:

**User Input**:
```bash
php bin/console app:greet John --uppercase
```

**Step 1: Application Start**
```php
// bin/console
$application = new Application();
$application->add(new GreetCommand());
$application->run();
```

**Step 2: Input Parsing**
```php
// Application::run()
$input = new ArgvInput(); // Parses $_SERVER['argv']

// ArgvInput result:
[
    'arguments' => ['name' => 'John'],
    'options' => ['uppercase' => true]
]
```

**Step 3: Command Discovery**
```php
// Application::run()
$commandName = 'app:greet';
$command = $application->find($commandName); // Returns GreetCommand
```

**Step 4: Command Execution**
```php
// Application::run()
$output = new ConsoleOutput();
$exitCode = $command->run($input, $output);

// Inside Command::run()
$this->bind($input);      // Bind argument/option definitions
$this->validate($input);  // Check required arguments
$this->initialize(...);   // Optional setup
$this->interact(...);     // Optional interactive questions
$exitCode = $this->execute(...); // Main logic
```

**Step 5: Execute Logic**
```php
// GreetCommand::execute()
$name = $input->getArgument('name');        // 'John'
$uppercase = $input->getOption('uppercase'); // true

$greeting = "Hello, $name!";                // 'Hello, John!'
if ($uppercase) {
    $greeting = strtoupper($greeting);      // 'HELLO, JOHN!'
}

$output->writeln($greeting);
return Command::SUCCESS; // 0
```

**Step 6: Output Formatting**
```php
// ConsoleOutput::writeln()
$message = 'HELLO, JOHN!';

// If decorated, apply colors
if ($this->isDecorated()) {
    $message = $this->format($message); // Apply ANSI codes
}

fwrite(STDOUT, $message . PHP_EOL);
```

**Step 7: Return to Shell**
```php
// Application::run()
return $exitCode; // 0

// bin/console
exit($application->run()); // exit(0)
```

## Key Design Patterns

### 1. Command Pattern
Each command encapsulates a specific action.

### 2. Strategy Pattern
Different Input/Output implementations can be swapped.

### 3. Template Method Pattern
Command lifecycle (initialize → interact → execute) is a template.

### 4. Decorator Pattern
SymfonyStyle decorates basic Output with high-level methods.

### 5. Factory Pattern
Application creates Input/Output instances.

## Performance Considerations

1. **Lazy Loading**: Commands are only instantiated when needed
2. **Stream Output**: Output is written directly to stream (no buffering)
3. **Minimal Parsing**: Input parsing is done once
4. **Efficient Rendering**: Progress bars use `\r` to overwrite same line

## Extension Points

You can extend the console framework by:

1. **Custom Input Classes**: Parse input from different sources
2. **Custom Output Classes**: Write to files, logs, etc.
3. **Custom Helpers**: Create reusable output components
4. **Custom Styles**: Define your own formatting styles
5. **Event Listeners**: Hook into command lifecycle (not shown in basic implementation)

## Comparison with Real Symfony Console

This educational implementation includes:
- ✅ Basic command structure
- ✅ Argument and option parsing
- ✅ Input/Output abstraction
- ✅ Progress bars and tables
- ✅ SymfonyStyle API
- ✅ Command attributes

Real Symfony Console additionally has:
- Event system (ConsoleEvents)
- Command loader (lazy loading)
- Helper set (multiple helpers)
- Auto-completion
- Signal handling
- Process helper
- Question helper with validation
- Multiple input sources
- Output formatters
- Cursor manipulation
- And much more...

This implementation is intentionally simplified for educational purposes while maintaining the core concepts and architecture.
