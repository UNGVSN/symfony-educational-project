# Running Tests and Examples

## Prerequisites

- PHP 8.2 or higher
- Composer

## Setup

1. **Install dependencies**:
   ```bash
   cd /home/ungvsn/symfony-educational-project/framework-rebuild/07-dependency-injection
   composer install
   ```

## Running Tests

### All Tests

```bash
./vendor/bin/phpunit
```

**Expected Output**:
```
PHPUnit 10.x.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.x
Configuration: phpunit.xml

.......................................................    55 / 55 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (55 tests, 150 assertions)
```

### Specific Test Class

```bash
# Container tests
./vendor/bin/phpunit tests/ContainerTest.php

# Builder tests
./vendor/bin/phpunit tests/ContainerBuilderTest.php

# Autowiring tests
./vendor/bin/phpunit tests/AutowirePassTest.php

# Integration tests
./vendor/bin/phpunit tests/IntegrationTest.php
```

### Specific Test Method

```bash
./vendor/bin/phpunit --filter testSetAndGetService tests/ContainerTest.php
```

### With Coverage

```bash
# HTML coverage report
./vendor/bin/phpunit --coverage-html coverage

# Then open in browser
xdg-open coverage/index.html  # Linux
open coverage/index.html      # macOS
```

### Verbose Output

```bash
./vendor/bin/phpunit --verbose
```

### Stop on Failure

```bash
./vendor/bin/phpunit --stop-on-failure
```

## Running the Demo

```bash
php examples/demo.php
```

**Expected Output**:
```
=== Dependency Injection Container Demo ===

1. Basic Service Registration
--------------------------------------------------
Logger service created: Psr\Log\NullLogger
Same instance on second call: Yes

2. Service with Dependencies
--------------------------------------------------
Service created with injected dependency
Dependency type: stdClass

3. Parameters
--------------------------------------------------
App Name: Demo Application
App Version: 1.0.0

4. Autowiring
--------------------------------------------------
Service autowired successfully
Logger injected: Psr\Log\NullLogger

5. Tagged Services
--------------------------------------------------
Found 2 event listeners:
  - listener1: event=user.created, priority=10
  - listener2: event=user.updated, priority=5

6. Factory Pattern
--------------------------------------------------
Product created by factory
Product config: factory config

7. Setter Injection
--------------------------------------------------
Service created with setter injection
Logger set via setter: Yes

8. Service Aliases
--------------------------------------------------
All references point to same instance: Yes

=== Demo Complete ===
```

## Manual Testing

### Test Container Basics

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\Container;

$container = new Container();
$container->set('service', new stdClass());

var_dump($container->has('service')); // bool(true)
var_dump($container->get('service')); // object(stdClass)
```

### Test Service Registration

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
$container->register('logger', Psr\Log\NullLogger::class);
$container->compile();

$logger = $container->get('logger');
var_dump($logger instanceof Psr\Log\NullLogger); // bool(true)
```

### Test Autowiring

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Compiler\AutowirePass;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MyService {
    public function __construct(public readonly LoggerInterface $logger) {}
}

$container = new ContainerBuilder();
$container->register('logger', NullLogger::class);
$container->setAlias(LoggerInterface::class, 'logger');
$container->register('my.service', MyService::class)->setAutowired(true);
$container->addCompilerPass(new AutowirePass());
$container->compile();

$service = $container->get('my.service');
var_dump($service->logger instanceof NullLogger); // bool(true)
```

### Test Tagged Services

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();

$container->register('service1', stdClass::class)
    ->addTag('my.tag', ['priority' => 10]);

$container->register('service2', stdClass::class)
    ->addTag('my.tag', ['priority' => 5]);

$tagged = $container->findTaggedServiceIds('my.tag');
var_dump(count($tagged)); // int(2)
var_dump($tagged['service1'][0]['priority']); // int(10)
```

## Debugging Tests

### Enable Debug Output

```php
// In tests, add:
protected function setUp(): void
{
    parent::setUp();
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
```

### Print Container State

```php
// Add to test:
public function testDebug(): void
{
    $container = new ContainerBuilder();
    $container->register('service', stdClass::class);

    echo "\nDefinitions:\n";
    print_r($container->getDefinitions());

    echo "\nService IDs:\n";
    print_r($container->getServiceIds());
}
```

### Test Specific Features

```bash
# Test only autowiring
./vendor/bin/phpunit --filter Autowire

# Test only compilation
./vendor/bin/phpunit --filter compile

# Test only tagged services
./vendor/bin/phpunit --filter Tag
```

## Common Issues

### Tests Fail with "Class not found"

**Solution**: Run `composer install` or `composer dump-autoload`

### PHPUnit not found

**Solution**: Install dev dependencies
```bash
composer install --dev
```

### PHP Version Error

**Solution**: Ensure PHP 8.2+ is installed
```bash
php -v
```

### Memory Limit

**Solution**: Increase memory limit
```bash
php -d memory_limit=512M ./vendor/bin/phpunit
```

## Performance Testing

### Measure Container Build Time

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Compiler\AutowirePass;

$start = microtime(true);

$container = new ContainerBuilder();

// Register 100 services
for ($i = 0; $i < 100; $i++) {
    $container->register("service_{$i}", stdClass::class);
}

$container->addCompilerPass(new AutowirePass());
$container->compile();

$time = microtime(true) - $start;
echo "Container build time: " . number_format($time * 1000, 2) . " ms\n";
```

### Measure Service Retrieval Time

```php
<?php
require 'vendor/autoload.php';

use App\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
$container->register('service', stdClass::class);
$container->compile();

// First access (instantiation)
$start = microtime(true);
$service = $container->get('service');
$firstTime = microtime(true) - $start;

// Second access (cached)
$start = microtime(true);
$service = $container->get('service');
$cachedTime = microtime(true) - $start;

echo "First access: " . number_format($firstTime * 1000000, 2) . " μs\n";
echo "Cached access: " . number_format($cachedTime * 1000000, 2) . " μs\n";
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.2', '8.3']

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php }}
        coverage: xdebug

    - name: Install dependencies
      run: composer install

    - name: Run tests
      run: ./vendor/bin/phpunit --coverage-text
```

## Next Steps

1. Run all tests: `./vendor/bin/phpunit`
2. Run demo: `php examples/demo.php`
3. Try exercises: See [EXERCISES.md](EXERCISES.md)
4. Read documentation: See [README.md](README.md)
5. Explore examples: Check [examples/](examples/) directory

## Getting Help

- Check [QUICKSTART.md](QUICKSTART.md) for basic usage
- Read [README.md](README.md) for detailed concepts
- Review test files for examples
- Try [EXERCISES.md](EXERCISES.md) for practice
