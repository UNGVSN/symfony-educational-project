# Miscellaneous - Practice Questions

Test your knowledge of Symfony's miscellaneous components and features.

---

## Questions

### Question 1: Environment Variables

What is the correct order of precedence for environment variable files in Symfony, from highest to lowest priority?

**Options:**
A) `.env` → `.env.local` → `.env.{ENV}` → Real environment variables
B) Real environment variables → `.env.{ENV}.local` → `.env.local` → `.env.{ENV}` → `.env`
C) `.env.local` → `.env` → `.env.{ENV}.local` → Real environment variables
D) `.env.{ENV}` → `.env.{ENV}.local` → `.env` → `.env.local`

---

### Question 2: Cache Component

Complete the code to cache the result of an expensive database query for 1 hour:

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductRepository
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function findAll(): array
    {
        return $this->cache->get('products', function (ItemInterface $item) {
            // TODO: Configure cache expiration
            // TODO: Return expensive database query result
        });
    }
}
```

---

### Question 3: DotEnv Processors

Which environment variable processor would you use to decode a base64-encoded database password?

```yaml
doctrine:
    dbal:
        password: '%env(________:DATABASE_PASSWORD)%'
```

**Options:**
A) `decode`
B) `base64`
C) `string`
D) `decrypt`

---

### Question 4: Debugging Tools

What is the difference between `dump()` and `dd()` functions in Symfony?

---

### Question 5: Custom Error Pages

Where should you place a custom 404 error page template in a Symfony application?

**Options:**
A) `templates/errors/404.html.twig`
B) `templates/bundles/TwigBundle/Exception/error404.html.twig`
C) `templates/exception/404.html.twig`
D) `templates/errors/not_found.html.twig`

---

### Question 6: Process Component

Complete the code to run a Git command and handle errors:

```php
use Symfony\Component\Process\Process;

class GitService
{
    public function getStatus(): string
    {
        $process = new Process(['git', 'status']);
        // TODO: Run the process

        // TODO: Check if successful, throw exception if not

        // TODO: Return the output
    }
}
```

---

### Question 7: Serializer Groups

Given this entity:

```php
use Symfony\Component\Serializer\Annotation\Groups;

class Product
{
    #[Groups(['product:read', 'product:write'])]
    private ?int $id = null;

    #[Groups(['product:read', 'product:write'])]
    private string $name;

    #[Groups(['product:admin'])]
    private float $cost;

    #[Groups(['product:read'])]
    private float $price;
}
```

What fields will be serialized when using groups `['product:read']`?

**Options:**
A) `id`, `name`, `price`
B) `id`, `name`, `cost`, `price`
C) `name`, `price`
D) All fields

---

### Question 8: Messenger Component

What is the purpose of message stamps in the Messenger component?

---

### Question 9: Async Message Processing

Complete the Messenger configuration to route `SendEmailMessage` to an async transport:

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            # TODO: Route SendEmailMessage to async transport
```

---

### Question 10: Mailer Component

What's wrong with this email code?

```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function sendWelcome(User $user): void
    {
        $email = new Email();
        $email->to($user->getEmail());
        $email->subject('Welcome!');

        $this->mailer->send($email);
    }
}
```

---

### Question 11: File Operations

Using the Filesystem component, write code to:
1. Create a directory `/var/uploads/documents`
2. Copy a file from `/tmp/upload.pdf` to `/var/uploads/documents/file.pdf`
3. Set permissions to 0644

```php
use Symfony\Component\Filesystem\Filesystem;

$filesystem = new Filesystem();

// TODO: Implement the operations
```

---

### Question 12: Finder Component

Complete the code to find all PHP files in the `src/` directory that:
- Are not test files (don't end with `Test.php`)
- Contain the word "Service"
- Were modified in the last 7 days

```php
use Symfony\Component\Finder\Finder;

$finder = new Finder();
// TODO: Configure the finder
```

---

### Question 13: Lock Component

Why is using locks important in this scenario?

```php
class PaymentProcessor
{
    public function processPayment(Order $order): void
    {
        $lock = $this->lockFactory->createLock('order-' . $order->getId());

        if (!$lock->acquire()) {
            throw new \RuntimeException('Order is being processed');
        }

        try {
            $this->chargeCustomer($order);
            $order->setStatus('paid');
            $this->entityManager->flush();
        } finally {
            $lock->release();
        }
    }
}
```

---

### Question 14: Clock Component

How would you test this code that depends on the current time?

```php
class SubscriptionService
{
    public function isExpired(Subscription $subscription): bool
    {
        $now = Clock::get()->now();
        return $subscription->getExpiresAt() < $now;
    }
}
```

---

### Question 15: ExpressionLanguage

What will this expression evaluate to?

```php
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

$expressionLanguage = new ExpressionLanguage();

$result = $expressionLanguage->evaluate(
    'price * quantity * (1 - discount)',
    [
        'price' => 100,
        'quantity' => 3,
        'discount' => 0.1,
    ]
);
```

**Options:**
A) 270
B) 300
C) 297
D) 330

---

### Question 16: Translation

Complete the translation file and PHP code:

```yaml
# translations/messages.en.yaml
cart:
    items: # TODO: Add pluralization for 0, 1, and multiple items
```

```php
// In controller
$message = $this->translator->trans(/* TODO: Complete */);
```

---

### Question 17: Cache Tags

What is the advantage of using cache tags?

```php
class ProductService
{
    public function findByCategory(string $category): array
    {
        return $this->cache->get(
            'products_category_' . $category,
            function (ItemInterface $item) use ($category) {
                $item->tag(['products', 'category_' . $category]);
                return $this->fetchFromDatabase($category);
            }
        );
    }

    public function clearCategoryCache(string $category): void
    {
        $this->cache->invalidateTags(['category_' . $category]);
    }
}
```

---

### Question 18: Secrets Management

What are the benefits of using Symfony's secrets management system instead of regular environment variables?

---

### Question 19: Production Deployment

Order these deployment steps correctly:

A) Install assets
B) Clear cache
C) Run database migrations
D) Install dependencies with `--no-dev --optimize-autoloader`
E) Warm up cache

**Correct order:**
1. ____
2. ____
3. ____
4. ____
5. ____

---

### Question 20: Multiple Mailers

Complete the configuration and service to use different mailers for different purposes:

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        default_mailer: main
        mailers:
            main:
                dsn: '%env(MAILER_DSN)%'
            # TODO: Add transactional mailer
```

```php
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    public function __construct(
        // TODO: Inject the transactional mailer
    ) {}
}
```

---

## Answers

### Answer 1: Environment Variables

**Correct Answer: B**

The correct order of precedence is:
1. Real environment variables (highest)
2. `.env.{ENV}.local`
3. `.env.local` (not loaded in test)
4. `.env.{ENV}`
5. `.env` (lowest)

This allows you to have committed defaults (`.env`, `.env.{ENV}`) and local overrides that aren't committed (`.env.local`, `.env.{ENV}.local`), with real environment variables taking ultimate precedence.

---

### Answer 2: Cache Component

```php
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ProductRepository
{
    public function __construct(
        private CacheInterface $cache,
    ) {}

    public function findAll(): array
    {
        return $this->cache->get('products', function (ItemInterface $item) {
            // Configure cache expiration for 1 hour (3600 seconds)
            $item->expiresAfter(3600);

            // Optional: Add tags for better cache invalidation
            $item->tag(['products']);

            // Return expensive database query result
            return $this->fetchAllProductsFromDatabase();
        });
    }

    private function fetchAllProductsFromDatabase(): array
    {
        // Expensive database operation
        return [];
    }
}
```

**Key points:**
- Use `expiresAfter()` to set TTL in seconds
- The callback is only executed on cache miss
- Consider adding tags for group invalidation
- Alternative: `expiresAt()` for absolute expiration time

---

### Answer 3: DotEnv Processors

**Correct Answer: B**

```yaml
doctrine:
    dbal:
        password: '%env(base64:DATABASE_PASSWORD)%'
```

The `base64` processor decodes base64-encoded values. Other useful processors:
- `string` - casts to string
- `int` - casts to integer
- `bool` - casts to boolean
- `json` - decodes JSON
- `file` - returns file contents
- `resolve` - resolves parameter references
- You can chain processors: `%env(json:file:CONFIG_PATH)%`

---

### Answer 4: Debugging Tools

**Differences between `dump()` and `dd()`:**

1. **dump()**
   - Outputs variable information
   - Continues script execution
   - Can dump multiple variables: `dump($var1, $var2, $var3)`
   - Output appears in Web Debug Toolbar or console

2. **dd()** (dump and die)
   - Outputs variable information
   - **Stops script execution** (like `die()` or `exit()`)
   - Useful for quick debugging
   - Should not be left in production code

Example:
```php
dump($user);        // Shows user data, continues
dump($order);       // Shows order data, continues
echo "Still running";

dd($product);       // Shows product data, stops here
echo "Never reached";
```

---

### Answer 5: Custom Error Pages

**Correct Answer: B**

Custom error pages should be placed in:
```
templates/bundles/TwigBundle/Exception/error404.html.twig
```

For different error codes:
- `error404.html.twig` - 404 Not Found
- `error403.html.twig` - 403 Forbidden
- `error500.html.twig` - 500 Internal Server Error
- `error.html.twig` - Generic error (fallback)

Environment-specific:
- `error404.dev.html.twig` - Development only
- `error404.prod.html.twig` - Production only

---

### Answer 6: Process Component

```php
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitService
{
    public function getStatus(): string
    {
        $process = new Process(['git', 'status']);

        // Run the process
        $process->run();

        // Check if successful, throw exception if not
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Return the output
        return $process->getOutput();
    }
}
```

**Additional options:**
```php
// Set timeout
$process->setTimeout(60);

// Set working directory
$process->setWorkingDirectory('/var/www/project');

// Get error output
$errorOutput = $process->getErrorOutput();

// Get exit code
$exitCode = $process->getExitCode();

// Run with real-time output
$process->run(function ($type, $buffer) {
    echo $buffer;
});
```

---

### Answer 7: Serializer Groups

**Correct Answer: A** - `id`, `name`, `price`

Fields serialized with `['product:read']` group:
- `id` - has `product:read` group ✓
- `name` - has `product:read` group ✓
- `cost` - only has `product:admin` group ✗
- `price` - has `product:read` group ✓

**Explanation:**
- Only fields marked with the specified group(s) are included
- `cost` requires `product:admin` group
- Multiple groups can be specified: `['product:read', 'product:admin']` would include all fields

---

### Answer 8: Messenger Component

**Purpose of message stamps:**

Message stamps provide **metadata** about messages without modifying the message itself. They are used for:

1. **Routing control**
   ```php
   new TransportNamesStamp(['async_redis'])
   ```

2. **Delays**
   ```php
   new DelayStamp(5000)  // Delay 5 seconds
   ```

3. **Priority**
   ```php
   new AmqpStamp(null, AMQP_NOPARAM, ['priority' => 10])
   ```

4. **Tracking**
   ```php
   new SentStamp('transport_name')
   new ReceivedStamp('transport_name')
   ```

5. **Error handling**
   ```php
   new RedeliveryStamp(3, 'error message')
   ```

Stamps are **immutable** and **don't affect message serialization**.

---

### Answer 9: Async Message Processing

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            # Route SendEmailMessage to async transport
            App\Message\SendEmailMessage: async
```

**Alternative configurations:**
```yaml
routing:
    # Multiple messages to same transport
    App\Message\SendEmailMessage: async
    App\Message\ProcessImageMessage: async

    # Multiple transports for one message
    App\Message\ImportantMessage: [async, backup]

    # All messages in namespace
    'App\Message\*': async
```

**Process messages:**
```bash
php bin/console messenger:consume async
```

---

### Answer 10: Mailer Component

**Problems with the code:**

1. **Missing constructor** - `MailerInterface` not injected
2. **Missing sender** - No `from()` address
3. **Missing content** - No `text()` or `html()` content
4. **No error handling** - Should handle potential exceptions

**Corrected code:**
```php
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    public function sendWelcome(User $user): void
    {
        $email = (new Email())
            ->from('noreply@example.com')
            ->to($user->getEmail())
            ->subject('Welcome!')
            ->html('<h1>Welcome to our platform!</h1>')
            ->text('Welcome to our platform!');

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Handle email sending failure
            throw new \RuntimeException('Failed to send email', 0, $e);
        }
    }
}
```

---

### Answer 11: File Operations

```php
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

$filesystem = new Filesystem();

try {
    // 1. Create directory
    $filesystem->mkdir('/var/uploads/documents');

    // 2. Copy file
    $filesystem->copy(
        '/tmp/upload.pdf',
        '/var/uploads/documents/file.pdf',
        overwriteNewerFiles: true
    );

    // 3. Set permissions to 0644
    $filesystem->chmod('/var/uploads/documents/file.pdf', 0644);

} catch (IOExceptionInterface $exception) {
    echo 'Error: ' . $exception->getMessage();
    echo 'Path: ' . $exception->getPath();
}
```

**Additional operations:**
```php
// Check if exists
if ($filesystem->exists('/path/to/file')) {
    // File exists
}

// Remove file or directory
$filesystem->remove('/path/to/file');

// Create empty file
$filesystem->touch('/path/to/file');

// Write content to file
$filesystem->dumpFile('/path/to/file', 'content');

// Mirror directory
$filesystem->mirror('/source', '/target');
```

---

### Answer 12: Finder Component

```php
use Symfony\Component\Finder\Finder;

$finder = new Finder();

$finder->files()
    ->in('src/')
    ->name('*.php')
    ->notName('*Test.php')
    ->contains('Service')
    ->date('since 7 days ago');

foreach ($finder as $file) {
    echo $file->getRealPath() . PHP_EOL;
}
```

**Alternative date formats:**
```php
->date('> now - 7 days')
->date('>= ' . date('Y-m-d', strtotime('-7 days')))
```

**Additional useful methods:**
```php
// Size constraints
->size('< 1M')
->size('>= 10K')

// Depth
->depth('< 3')
->depth('== 0')  // Top level only

// Sort
->sortByName()
->sortByModifiedTime()
->sortBySize()

// Content
->notContains('deprecated')

// Path patterns
->path('Controller')
->notPath('Tests')
```

---

### Answer 13: Lock Component

**Why locks are important in payment processing:**

1. **Prevent duplicate charges**
   - Without locks, multiple processes could charge the customer multiple times
   - Race condition if two requests process the same order simultaneously

2. **Data integrity**
   - Ensures order status is updated atomically
   - Prevents one process from overwriting another's changes

3. **Consistent state**
   - Guarantees only one payment processor handles an order at a time
   - Prevents "lost updates" in the database

**Example scenario without locks:**
```
Time  Process A              Process B
0s    Read order (unpaid)
1s                          Read order (unpaid)
2s    Charge customer $100
3s                          Charge customer $100 (duplicate!)
4s    Set status = paid
5s                          Set status = paid
Result: Customer charged twice!
```

**With locks:**
```
Time  Process A              Process B
0s    Acquire lock ✓
1s                          Try lock (blocked)
2s    Charge customer $100
3s    Set status = paid
4s    Release lock
5s                          Acquire lock ✓
6s                          See status=paid, skip
```

---

### Answer 14: Clock Component

**Testing time-dependent code:**

```php
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\Clock;

class SubscriptionServiceTest extends TestCase
{
    public function testExpiredSubscription(): void
    {
        // Create a mock clock set to specific time
        $clock = new MockClock('2024-06-15 12:00:00');
        Clock::set($clock);

        // Create subscription that expired yesterday
        $subscription = new Subscription(
            expiresAt: new \DateTime('2024-06-14 12:00:00')
        );

        $service = new SubscriptionService();

        // Test
        $this->assertTrue($service->isExpired($subscription));
    }

    public function testActiveSubscription(): void
    {
        $clock = new MockClock('2024-06-15 12:00:00');
        Clock::set($clock);

        // Subscription expires tomorrow
        $subscription = new Subscription(
            expiresAt: new \DateTime('2024-06-16 12:00:00')
        );

        $service = new SubscriptionService();

        $this->assertFalse($service->isExpired($subscription));
    }

    public function testSubscriptionExpirationOverTime(): void
    {
        $clock = new MockClock('2024-06-15 12:00:00');
        Clock::set($clock);

        $subscription = new Subscription(
            expiresAt: new \DateTime('2024-06-16 12:00:00')
        );

        $service = new SubscriptionService();

        // Before expiration
        $this->assertFalse($service->isExpired($subscription));

        // Advance time past expiration
        $clock->modify('+2 days');

        // After expiration
        $this->assertTrue($service->isExpired($subscription));
    }

    protected function tearDown(): void
    {
        // Reset clock to real time
        Clock::set();
    }
}
```

**Service should accept clock injection:**
```php
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\Clock;

class SubscriptionService
{
    private ClockInterface $clock;

    public function __construct(?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? Clock::get();
    }

    public function isExpired(Subscription $subscription): bool
    {
        $now = $this->clock->now();
        return $subscription->getExpiresAt() < $now;
    }
}
```

---

### Answer 15: ExpressionLanguage

**Correct Answer: A** - 270

**Calculation:**
```
price * quantity * (1 - discount)
= 100 * 3 * (1 - 0.1)
= 100 * 3 * 0.9
= 300 * 0.9
= 270
```

**Common uses of ExpressionLanguage:**
```php
// Discount rules
$expressionLanguage->evaluate(
    'total > 100 and items >= 5',
    ['total' => 150, 'items' => 6]
);  // true

// Access control
$expressionLanguage->evaluate(
    'user.age >= 18 and user.verified',
    ['user' => $user]
);

// Dynamic configuration
$expressionLanguage->evaluate(
    'quantity * price * (1 + taxRate)',
    ['quantity' => 2, 'price' => 50, 'taxRate' => 0.2]
);  // 120
```

---

### Answer 16: Translation

```yaml
# translations/messages.en.yaml
cart:
    items: '{0} No items|{1} One item|]1,Inf[ %count% items'
```

```php
// In controller
$itemCount = 5;
$message = $this->translator->trans('cart.items', ['%count%' => $itemCount]);
// Result: "5 items"
```

**With ICU MessageFormat:**
```yaml
# translations/messages+intl-icu.en.yaml
cart:
    items: >
        {count, plural,
            =0 {No items}
            one {One item}
            other {# items}
        }
```

```php
$message = $this->translator->trans('cart.items', ['count' => 5]);
```

**In Twig:**
```twig
{{ 'cart.items'|trans({'%count%': cart.items|length}) }}
```

---

### Answer 17: Cache Tags

**Advantages of cache tags:**

1. **Granular invalidation**
   - Can invalidate related cache entries without clearing everything
   - Example: Clear all product caches: `invalidateTags(['products'])`
   - Example: Clear specific category: `invalidateTags(['category_electronics'])`

2. **Avoid cache stampede**
   - Don't need to clear all caches when one product changes
   - Only invalidate affected entries

3. **Flexible grouping**
   - One cache entry can have multiple tags
   - Can invalidate by different criteria

4. **Better performance**
   - More targeted cache clearing
   - Less overhead than rebuilding entire cache

**Example:**
```php
// Tag a cache entry with multiple tags
$item->tag(['products', 'category_' . $category, 'brand_' . $brand]);

// Later, clear all products
$this->cache->invalidateTags(['products']);

// Or clear just one category
$this->cache->invalidateTags(['category_electronics']);

// Or clear one brand
$this->cache->invalidateTags(['brand_apple']);
```

**Without tags:**
```php
// Would need to manually delete each key
$this->cache->delete('products_all');
$this->cache->delete('products_category_electronics');
$this->cache->delete('products_category_books');
// ... many more deletions
```

---

### Answer 18: Secrets Management

**Benefits of Symfony's secrets management:**

1. **Encryption**
   - Secrets are encrypted with strong encryption keys
   - Safe to commit encrypted secrets to version control
   - Environment variables are stored in plain text

2. **Separation of concerns**
   - Encryption keys stored separately from code
   - Production keys never on development machines
   - Different keys for different environments

3. **Version control friendly**
   - Encrypted secrets can be committed
   - Track changes to secrets over time
   - Team can update secrets without sharing plain text

4. **No production server access needed**
   - Update secrets locally, commit encrypted version
   - Secrets automatically decrypted in production
   - Don't need SSH access to update secrets

5. **Audit trail**
   - Git history shows when secrets changed
   - Can review what changed without seeing values

**Setup:**
```bash
# Development
php bin/console secrets:generate-keys
php bin/console secrets:set API_KEY

# Production keys (do not commit)
php bin/console secrets:generate-keys --env=prod
# Store decrypt private key securely on production server
```

**File structure:**
```
config/
├── secrets/
│   ├── dev/
│   │   ├── dev.decrypt.private.php   # Not committed
│   │   ├── dev.encrypt.public.php    # Committed
│   │   └── dev.API_KEY.encrypted     # Committed
│   └── prod/
│       ├── prod.decrypt.private.php  # Not committed, only on prod
│       ├── prod.encrypt.public.php   # Committed
│       └── prod.API_KEY.encrypted    # Committed
```

**Comparison:**

| Feature | Environment Variables | Secrets Management |
|---------|----------------------|-------------------|
| Encryption | No | Yes |
| Version control | No | Yes (encrypted) |
| Change tracking | No | Yes (via git) |
| Team sharing | Insecure | Secure |
| Production updates | Requires server access | Can be committed |

---

### Answer 19: Production Deployment

**Correct order:**

1. **D** - Install dependencies with `--no-dev --optimize-autoloader`
2. **B** - Clear cache
3. **C** - Run database migrations
4. **A** - Install assets
5. **E** - Warm up cache

**Complete deployment script:**
```bash
#!/bin/bash

# 1. Install dependencies (production only, optimized)
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Clear cache
php bin/console cache:clear --env=prod --no-warmup

# 3. Run database migrations
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# 4. Install assets
php bin/console assets:install public --env=prod

# 5. Warm up cache
php bin/console cache:warmup --env=prod

# Optional: Dump environment variables for performance
composer dump-env prod

# Optional: Clear OPcache
php bin/console cache:pool:clear cache.global_clearer
```

**Rationale:**
- Dependencies first (code needed for everything else)
- Clear old cache before running commands
- Migrations before cache warmup (to have correct schema)
- Assets before cache warmup (templates may reference assets)
- Cache warmup last (needs everything else in place)

---

### Answer 20: Multiple Mailers

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        default_mailer: main
        mailers:
            main:
                dsn: '%env(MAILER_DSN)%'

            # Add transactional mailer
            transactional:
                dsn: '%env(MAILER_TRANSACTIONAL_DSN)%'

            # Optional: marketing mailer
            marketing:
                dsn: '%env(MAILER_MARKETING_DSN)%'
```

```php
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        // Inject the transactional mailer
        #[Target('transactional')]
        private MailerInterface $transactionalMailer,

        // Can also inject other mailers
        #[Target('marketing')]
        private MailerInterface $marketingMailer,
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new Email())
            ->from('orders@example.com')
            ->to($order->getCustomer()->getEmail())
            ->subject('Order Confirmation')
            ->html('Your order has been confirmed');

        // Use transactional mailer
        $this->transactionalMailer->send($email);
    }

    public function sendNewsletter(array $subscribers): void
    {
        foreach ($subscribers as $subscriber) {
            $email = (new Email())
                ->from('newsletter@example.com')
                ->to($subscriber->getEmail())
                ->subject('Monthly Newsletter')
                ->html('Newsletter content...');

            // Use marketing mailer
            $this->marketingMailer->send($email);
        }
    }
}
```

**Environment variables:**
```bash
# .env
MAILER_DSN=smtp://default@localhost
MAILER_TRANSACTIONAL_DSN=smtp://transactional@smtp.sendgrid.net:587
MAILER_MARKETING_DSN=smtp://marketing@smtp.mailchimp.com:587
```

**Why use multiple mailers:**
- Different sending limits/rates
- Different providers for different purposes
- Separate reputation/IP addresses
- Cost optimization
- Reliability (failover)

---

## Summary

These questions cover the essential concepts from Symfony's miscellaneous components:

- Configuration and environment management
- Caching strategies and invalidation
- Debugging and profiling
- Error handling
- Process execution
- Serialization
- Asynchronous messaging
- Email sending
- File operations
- Locking mechanisms
- Time testing
- Expression evaluation
- Internationalization
- Production deployment

Practice these concepts to build robust, professional Symfony applications!
