# Chapter 5: Troubleshooting

## Learning Objectives

- Master the Symfony Web Debug Toolbar
- Use the Symfony Profiler effectively
- Debug code with dump() and dd() functions
- Analyze and interpret log files
- Profile performance bottlenecks
- Troubleshoot common Symfony errors
- Use Xdebug for step-by-step debugging

## Prerequisites

Before starting this chapter, ensure you have:
- Completed Chapter 4 (Adopting a Methodology)
- A working Symfony project in development mode
- Basic understanding of HTTP requests and responses
- Familiarity with browser developer tools

## Step-by-Step Instructions

### 1. The Web Debug Toolbar

The Web Debug Toolbar appears at the bottom of every page in development mode.

#### Enable the Toolbar

Ensure you're in development environment:

```bash
# .env
APP_ENV=dev
APP_DEBUG=1
```

The toolbar is automatically enabled when:
- `APP_ENV=dev`
- Response is HTML
- Response status is not 3xx or 4xx (configurable)

#### Understanding Toolbar Sections

The toolbar shows:

1. **Request/Response** - HTTP method, status code, route
2. **Performance** - Execution time and memory usage
3. **Database** - Number of queries and execution time
4. **Cache** - Cache hits and misses
5. **Twig** - Template rendering time
6. **Logs** - Application logs and errors
7. **Security** - User authentication status
8. **Ajax** - AJAX request tracking

#### Customizing the Toolbar

Configure in `config/packages/dev/web_profiler.yaml`:

```yaml
web_profiler:
    toolbar: true
    intercept_redirects: false

framework:
    profiler:
        only_exceptions: false
        collect_serializer_data: true
```

### 2. The Symfony Profiler

Click any toolbar item to open the full Profiler.

#### Accessing the Profiler

```bash
# Direct URL
https://127.0.0.1:8000/_profiler/

# Or click any toolbar icon
```

#### Key Profiler Panels

**Request/Response Panel**

View complete request details:
- Request attributes (route, controller, parameters)
- Request headers
- Request data (GET, POST, cookies)
- Response headers
- Session data

```php
// Access profiler data in tests
$profile = $client->getProfile();
$token = $profile->getToken();
```

**Performance Panel**

Analyze execution time:
- Total execution time
- Controller execution time
- Memory usage
- Event listener execution order

**Database Panel**

Examine all database queries:
- Query count
- Total execution time
- Individual query times
- Duplicate queries (highlighted)
- Query parameters

**Twig Panel**

Template debugging:
- Rendered templates
- Template hierarchy
- Render times
- Template variables

**Logs Panel**

All application logs:
- Filter by level (debug, info, warning, error)
- Filter by channel
- View stack traces
- Links to code

### 3. Dump Functions

Symfony provides convenient debugging functions.

#### dump() Function

Output variable contents without stopping execution:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug', name: 'app_debug')]
    public function index(): Response
    {
        $user = $this->getUser();
        $data = ['name' => 'John', 'age' => 30];

        // Dump variables
        dump($user);
        dump($data);
        dump($_SERVER);

        // Execution continues
        return $this->render('debug/index.html.twig');
    }
}
```

#### dd() Function

Dump and die - output variable and stop execution:

```php
public function debug(): Response
{
    $query = $this->entityManager->createQuery('SELECT u FROM App\Entity\User u');
    $users = $query->getResult();

    // Dump and stop
    dd($users);

    // This line never executes
    return $this->render('debug/index.html.twig');
}
```

#### dump() in Twig Templates

```twig
{# templates/debug/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Debug Page</h1>

    {# Dump a variable #}
    {{ dump(user) }}

    {# Dump all variables #}
    {{ dump() }}

    {# Dump specific variables #}
    {{ dump(app.request) }}
    {{ dump(app.session) }}
{% endblock %}
```

#### VarDumper Component Features

The VarDumper component provides:
- Colored output
- Collapsible sections
- Type information
- Object properties
- Circular reference detection

Configure in `config/packages/dev/debug.yaml`:

```yaml
debug:
    dump_destination: "php://stderr"
    # Or dump to a file
    # dump_destination: "%kernel.logs_dir%/%kernel.environment%.dump"
```

### 4. Logging

#### Using the Logger Service

Inject and use the logger:

```php
<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user/{id}', name: 'user_show')]
    public function show(int $id, LoggerInterface $logger): Response
    {
        $logger->info('Viewing user profile', ['user_id' => $id]);

        try {
            $user = $this->findUser($id);
            $logger->debug('User found', ['user' => $user->getEmail()]);
        } catch (\Exception $e) {
            $logger->error('Failed to load user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $this->render('user/show.html.twig', ['user' => $user]);
    }
}
```

#### Log Levels

```php
$logger->debug('Detailed debug information');
$logger->info('Interesting events');
$logger->notice('Normal but significant events');
$logger->warning('Exceptional occurrences that are not errors');
$logger->error('Runtime errors');
$logger->critical('Critical conditions');
$logger->alert('Action must be taken immediately');
$logger->emergency('System is unusable');
```

#### Viewing Logs

```bash
# Tail development logs
tail -f var/log/dev.log

# View with filtering
grep ERROR var/log/dev.log

# View last 100 lines
tail -n 100 var/log/dev.log
```

#### Monolog Configuration

Configure logging in `config/packages/dev/monolog.yaml`:

```yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            channels: ["!event"]

        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine", "!console"]

        # Custom handler for specific channel
        custom:
            type: stream
            path: "%kernel.logs_dir%/custom.log"
            level: info
            channels: ["custom"]
```

#### Custom Log Channels

Create a custom logger:

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['security', 'payment']
```

Use it:

```php
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private LoggerInterface $paymentLogger, // Autowired as paymentLogger
    ) {
    }

    public function process(): void
    {
        $this->paymentLogger->info('Processing payment');
    }
}
```

### 5. Debugging Database Queries

#### Doctrine Query Logger

View queries in the profiler:

```php
use Doctrine\ORM\EntityManagerInterface;

#[Route('/users', name: 'user_list')]
public function list(EntityManagerInterface $em): Response
{
    // This query will appear in the profiler
    $users = $em->getRepository(User::class)->findAll();

    // Check the Database panel in the profiler
    return $this->render('user/list.html.twig', ['users' => $users]);
}
```

#### Enable Query Logging

```yaml
# config/packages/dev/doctrine.yaml
doctrine:
    dbal:
        logging: true
        profiling: true
```

#### Debug DQL Queries

```php
$query = $em->createQuery('SELECT u FROM App\Entity\User u WHERE u.email LIKE :pattern');
$query->setParameter('pattern', '%@example.com%');

// Get SQL
dump($query->getSQL());

// Get parameters
dump($query->getParameters());

// Execute and dump results
dd($query->getResult());
```

#### Detect N+1 Queries

The profiler highlights duplicate queries. Fix with joins:

```php
// Bad - N+1 queries
$users = $userRepository->findAll();
foreach ($users as $user) {
    echo $user->getProfile()->getBio(); // Extra query per user
}

// Good - Single query with join
$users = $userRepository->createQueryBuilder('u')
    ->leftJoin('u.profile', 'p')
    ->addSelect('p')
    ->getQuery()
    ->getResult();

foreach ($users as $user) {
    echo $user->getProfile()->getBio(); // No extra query
}
```

### 6. Performance Profiling

#### Stopwatch Component

Measure execution time:

```php
use Symfony\Component\Stopwatch\Stopwatch;

class HeavyService
{
    public function __construct(
        private Stopwatch $stopwatch,
    ) {
    }

    public function processData(array $data): void
    {
        $this->stopwatch->start('data_processing');

        // Heavy operation
        $this->stopwatch->start('data_validation');
        $this->validateData($data);
        $this->stopwatch->stop('data_validation');

        $this->stopwatch->start('data_transformation');
        $this->transformData($data);
        $this->stopwatch->stop('data_transformation');

        $event = $this->stopwatch->stop('data_processing');

        // Log timing
        dump($event->getDuration()); // milliseconds
        dump($event->getMemory()); // bytes
    }
}
```

View in profiler under Performance panel.

#### Blackfire Integration

Install Blackfire for deep profiling:

```bash
# Install Blackfire probe
# Follow instructions at https://blackfire.io

# Profile a request
blackfire curl https://127.0.0.1:8000/

# Profile CLI command
blackfire run php bin/console app:process
```

### 7. Debugging with Xdebug

#### Install Xdebug

```bash
# Install Xdebug
sudo apt-get install php8.3-xdebug

# Or via PECL
pecl install xdebug
```

#### Configure Xdebug

Edit `php.ini` or create `/etc/php/8.3/mods-available/xdebug.ini`:

```ini
zend_extension=xdebug.so

[xdebug]
xdebug.mode=debug,coverage
xdebug.start_with_request=trigger
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
```

#### VS Code Configuration

Install "PHP Debug" extension, then create `.vscode/launch.json`:

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

#### PhpStorm Configuration

1. Go to Settings → PHP → Debug
2. Set Xdebug port to 9003
3. Enable "Can accept external connections"
4. Install browser extension (Xdebug Helper)
5. Click "Start Listening for PHP Debug Connections"

#### Using Xdebug

```php
// Set breakpoints in your IDE
public function complexLogic(array $data): array
{
    $result = [];

    foreach ($data as $item) { // Set breakpoint here
        $processed = $this->process($item);
        $result[] = $processed;
    }

    return $result; // And here
}
```

Trigger Xdebug:
- Install browser extension
- Or add `XDEBUG_SESSION=1` to URL
- Or set cookie `XDEBUG_SESSION=PHPSTORM`

### 8. Common Debugging Scenarios

#### Debugging Forms

```php
use Symfony\Component\Form\FormInterface;

public function handleForm(Request $request): Response
{
    $form = $this->createForm(UserType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        dump($form->isValid()); // Check validity
        dump($form->getErrors(true)); // Get all errors
        dump($form->getData()); // Get submitted data

        if (!$form->isValid()) {
            dd($this->getErrorMessages($form)); // Helper method
        }
    }

    return $this->render('user/form.html.twig', ['form' => $form]);
}

private function getErrorMessages(FormInterface $form): array
{
    $errors = [];
    foreach ($form->getErrors(true) as $error) {
        $errors[] = $error->getMessage();
    }
    return $errors;
}
```

#### Debugging Security

```php
use Symfony\Component\Security\Core\Security;

#[Route('/admin', name: 'admin')]
public function admin(Security $security): Response
{
    dump($this->getUser()); // Current user
    dump($security->isGranted('ROLE_ADMIN')); // Check role
    dump($this->isGranted('ROLE_ADMIN')); // Shorthand

    // Check in profiler: Security panel
    return $this->render('admin/index.html.twig');
}
```

#### Debugging Events

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class DebugEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $this->logger->debug('Request event', [
            'route' => $event->getRequest()->attributes->get('_route'),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $this->logger->debug('Response event', [
            'status' => $event->getResponse()->getStatusCode(),
        ]);
    }
}
```

## Key Concepts Covered

### 1. Debugging Layers

Symfony provides multiple debugging layers:
- **Web Debug Toolbar**: Quick overview
- **Profiler**: Detailed analysis
- **Logs**: Historical record
- **Dump functions**: On-demand inspection
- **Xdebug**: Step-by-step debugging

### 2. Performance Optimization

Use profiler to identify:
- Slow database queries
- N+1 query problems
- Memory-intensive operations
- Template rendering bottlenecks

### 3. Production Debugging

In production:
- Disable toolbar and profiler
- Log errors to files
- Use external monitoring (Sentry, New Relic)
- Enable error reporting to admins only

## Exercises

### Exercise 1: Explore the Profiler

1. Create a controller with multiple database queries
2. View queries in the Database panel
3. Identify duplicate queries
4. Check execution time

<details>
<summary>Solution</summary>

```php
#[Route('/profile-test', name: 'profile_test')]
public function test(EntityManagerInterface $em): Response
{
    // Generate some queries
    $users = $em->getRepository(User::class)->findAll();

    foreach ($users as $user) {
        $posts = $em->getRepository(Post::class)->findBy(['author' => $user]);
        // N+1 query problem!
    }

    // Check profiler Database panel
    return new Response('Check profiler');
}
```

Fix the N+1 problem and compare in profiler.
</details>

### Exercise 2: Use Dump Functions

1. Create a controller action
2. Dump request data, session data, and user object
3. Use both dump() and dd()
4. Compare output

<details>
<summary>Solution</summary>

```php
#[Route('/dump-test', name: 'dump_test')]
public function dumpTest(Request $request): Response
{
    dump($request->query->all()); // Query parameters
    dump($request->request->all()); // POST data
    dump($request->headers->all()); // Headers
    dump($this->getUser()); // User object

    // Uncomment to test dd()
    // dd($request->getSession()->all());

    return $this->render('test/dump.html.twig');
}
```
</details>

### Exercise 3: Implement Logging

1. Create a service with logger
2. Log at different levels
3. View logs in profiler and file
4. Create a custom log channel

<details>
<summary>Solution</summary>

```php
namespace App\Service;

use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function processOrder(Order $order): void
    {
        $this->logger->info('Processing order', ['order_id' => $order->getId()]);

        try {
            $this->validateOrder($order);
            $this->logger->debug('Order validated');

            $this->saveOrder($order);
            $this->logger->info('Order saved successfully');
        } catch (\Exception $e) {
            $this->logger->error('Order processing failed', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```
</details>

### Exercise 4: Profile Performance

1. Create a slow operation
2. Use Stopwatch to measure it
3. View timing in profiler
4. Optimize and compare

<details>
<summary>Solution</summary>

```php
use Symfony\Component\Stopwatch\Stopwatch;

#[Route('/performance', name: 'performance')]
public function performance(Stopwatch $stopwatch): Response
{
    $stopwatch->start('slow_operation');

    // Simulate slow operation
    $result = [];
    for ($i = 0; $i < 10000; $i++) {
        $result[] = md5($i);
    }

    $event = $stopwatch->stop('slow_operation');

    return new Response(sprintf(
        'Operation took %d ms and used %d bytes',
        $event->getDuration(),
        $event->getMemory()
    ));
}
```
</details>

## Troubleshooting

### Issue: Toolbar not showing

Check:
```bash
# Ensure dev environment
echo $APP_ENV  # Should be "dev"
echo $APP_DEBUG  # Should be "1"

# Clear cache
php bin/console cache:clear
```

### Issue: Cannot access profiler

Check routes:
```bash
php bin/console debug:router | grep _profiler
```

Import routes in `config/routes/dev/web_profiler.yaml`:
```yaml
web_profiler_wdt:
    resource: '@WebProfilerBundle/Resources/config/routing/wdt.xml'
    prefix: /_wdt

web_profiler_profiler:
    resource: '@WebProfilerBundle/Resources/config/routing/profiler.xml'
    prefix: /_profiler
```

### Issue: Xdebug not connecting

Check:
```bash
php -v  # Should show "with Xdebug"
php -i | grep xdebug.mode  # Should show "debug"
```

## Summary

You've learned how to:
- Use the Web Debug Toolbar and Profiler
- Debug with dump() and dd() functions
- Analyze logs effectively
- Profile performance bottlenecks
- Use Xdebug for step-by-step debugging
- Troubleshoot common Symfony issues

## Next Steps

Continue to [Chapter 7: Setting up a Database](../07-database/README.md) to learn about database configuration and Docker.
