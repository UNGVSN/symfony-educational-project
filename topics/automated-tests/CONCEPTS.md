# Testing Concepts - Deep Dive

Comprehensive guide to testing concepts in Symfony applications with PHPUnit.

---

## Table of Contents

1. [Testing Pyramid](#testing-pyramid)
2. [PHPUnit Basics](#phpunit-basics)
3. [PHPUnit Configuration](#phpunit-configuration)
4. [Unit Testing Services](#unit-testing-services)
5. [Functional Testing with WebTestCase](#functional-testing-with-webtestcase)
6. [KernelTestCase for Integration Tests](#kerneltestcase-for-integration-tests)
7. [The Test Client](#the-test-client)
8. [The Crawler](#the-crawler)
9. [Making Requests and Asserting Responses](#making-requests-and-asserting-responses)
10. [Testing Forms](#testing-forms)
11. [Testing with Databases](#testing-with-databases)
12. [Mocking Services and Dependencies](#mocking-services-and-dependencies)
13. [PHPUnit Bridge and Deprecations](#phpunit-bridge-and-deprecations)
14. [Test Doubles](#test-doubles)
15. [Code Coverage](#code-coverage)
16. [Data Providers](#data-providers)

---

## Testing Pyramid

### Concept

The testing pyramid is a strategy that helps teams find the right balance between different test types.

```
                    ▲
                   /E\
                  /2E \          UI/End-to-End Tests
                 /Tests\         - Very Slow
                /_______\        - High Maintenance
               /         \       - High Confidence
              /Functional\
             /   Tests    \      Integration/Functional Tests
            /_____________\     - Medium Speed
           /               \    - Medium Maintenance
          /  Integration   \   - Medium Confidence
         /     Tests       \
        /___________________ \  Unit Tests
       /                     \ - Very Fast
      /      Unit Tests      \- Low Maintenance
     /_______________________\- Focused Confidence
```

### Detailed Breakdown

#### Unit Tests (Base - 70%)

**Purpose**: Test individual units of code in isolation

**Characteristics**:
- Test single methods or classes
- No external dependencies (database, network, filesystem)
- Use mocks and stubs for dependencies
- Run in milliseconds
- Should be the majority of your tests

**Example**:

```php
namespace App\Tests\Unit\Service;

use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function testCalculateWithTax(): void
    {
        // Arrange
        $calculator = new PriceCalculator();

        // Act
        $result = $calculator->calculateWithTax(100, 0.20);

        // Assert
        $this->assertEquals(120, $result);
    }

    public function testCalculateWithZeroTax(): void
    {
        $calculator = new PriceCalculator();
        $result = $calculator->calculateWithTax(100, 0);

        $this->assertEquals(100, $result);
    }

    public function testNegativePriceThrowsException(): void
    {
        $calculator = new PriceCalculator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');

        $calculator->calculateWithTax(-100, 0.20);
    }
}
```

#### Integration Tests (Middle - 20%)

**Purpose**: Test interaction between components

**Characteristics**:
- Test multiple components working together
- May use real dependencies (database, services)
- Use Symfony's service container
- Slower than unit tests but faster than functional tests

**Example**:

```php
namespace App\Tests\Integration\Service;

use App\Service\OrderService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderServiceTest extends KernelTestCase
{
    private OrderService $orderService;
    private ProductRepository $productRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->orderService = $container->get(OrderService::class);
        $this->productRepository = $container->get(ProductRepository::class);
    }

    public function testCreateOrderWithRealProducts(): void
    {
        // Uses real database and repositories
        $products = $this->productRepository->findBy(['active' => true], null, 3);

        $orderData = [
            'customer_id' => 1,
            'items' => array_map(fn($p) => [
                'product_id' => $p->getId(),
                'quantity' => 1
            ], $products)
        ];

        $order = $this->orderService->createOrder($orderData);

        $this->assertNotNull($order->getId());
        $this->assertCount(3, $order->getItems());
    }
}
```

#### Functional Tests (Middle - 10%)

**Purpose**: Test HTTP endpoints and user workflows

**Characteristics**:
- Test complete request/response cycle
- Simulate user interactions
- Test routing, controllers, views
- Can test authentication and authorization

**Example**:

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testCreatePostWorkflow(): void
    {
        $client = static::createClient();

        // 1. Visit the create form
        $crawler = $client->request('GET', '/posts/new');
        $this->assertResponseIsSuccessful();

        // 2. Fill and submit the form
        $form = $crawler->selectButton('Create Post')->form([
            'post[title]' => 'Integration Test Post',
            'post[content]' => 'This is a test post content',
            'post[published]' => true,
        ]);

        $client->submit($form);

        // 3. Should redirect to show page
        $this->assertResponseRedirects();
        $crawler = $client->followRedirect();

        // 4. Verify post is displayed
        $this->assertSelectorTextContains('h1', 'Integration Test Post');
        $this->assertSelectorTextContains('.post-content', 'This is a test post content');
    }
}
```

#### End-to-End Tests (Top - <5%)

**Purpose**: Test entire application from user perspective

**Characteristics**:
- Test real browser interactions
- Use tools like Panther, Selenium
- Test JavaScript interactions
- Very slow, expensive to maintain
- Only for critical paths

**Example**:

```php
namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class CheckoutTest extends PantherTestCase
{
    public function testCompleteCheckoutFlow(): void
    {
        $client = static::createPantherClient();

        // 1. Add product to cart
        $client->request('GET', '/products/1');
        $client->clickLink('Add to Cart');

        // 2. Go to checkout
        $client->clickLink('Checkout');

        // 3. Fill shipping form (with JavaScript validation)
        $client->submitForm('Continue', [
            'shipping[address]' => '123 Main St',
            'shipping[city]' => 'Springfield',
            'shipping[zip]' => '12345',
        ]);

        // 4. Complete payment
        $client->submitForm('Pay Now', [
            'payment[card_number]' => '4111111111111111',
            'payment[cvv]' => '123',
        ]);

        // 5. Verify confirmation page
        $this->assertSelectorTextContains('.success', 'Order confirmed');
    }
}
```

---

## PHPUnit Basics

### Test Case Structure

Every test class extends `TestCase` and follows conventions:

```php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    // Run before each test method
    protected function setUp(): void
    {
        parent::setUp();
        // Initialize test dependencies
    }

    // Run after each test method
    protected function tearDown(): void
    {
        // Clean up resources
        parent::tearDown();
    }

    // Run once before all tests in this class
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // One-time setup
    }

    // Run once after all tests in this class
    public static function tearDownAfterClass(): void
    {
        // One-time cleanup
        parent::tearDownAfterClass();
    }

    // Test method - must start with "test" or use @test annotation
    public function testSomething(): void
    {
        // Arrange
        $calculator = new Calculator();

        // Act
        $result = $calculator->add(2, 3);

        // Assert
        $this->assertEquals(5, $result);
    }

    /**
     * @test
     */
    public function it_can_subtract_numbers(): void
    {
        // Alternative naming using @test annotation
        $calculator = new Calculator();
        $result = $calculator->subtract(5, 3);
        $this->assertEquals(2, $result);
    }
}
```

### Assertions Reference

```php
class AssertionsReferenceTest extends TestCase
{
    public function testAllAssertions(): void
    {
        // === Equality Assertions ===

        // Basic equality (loose comparison)
        $this->assertEquals(10, '10'); // Passes
        $this->assertEquals([1, 2], [1, 2]);

        // Strict equality (type-safe)
        $this->assertSame(10, 10); // Type must match
        $this->assertNotSame(10, '10'); // Different types

        // Not equal
        $this->assertNotEquals(5, 10);

        // === Boolean Assertions ===

        $this->assertTrue(true);
        $this->assertFalse(false);

        // === Null Assertions ===

        $this->assertNull(null);
        $this->assertNotNull('value');

        // === Type Assertions ===

        $this->assertIsString('hello');
        $this->assertIsInt(42);
        $this->assertIsFloat(3.14);
        $this->assertIsBool(true);
        $this->assertIsArray([1, 2, 3]);
        $this->assertIsObject(new \stdClass());
        $this->assertIsNumeric('123');
        $this->assertIsCallable(fn() => true);
        $this->assertIsIterable([1, 2, 3]);

        // Instance of
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotInstanceOf(Admin::class, $user);

        // === Array Assertions ===

        $array = ['a' => 1, 'b' => 2, 'c' => 3];

        $this->assertIsArray($array);
        $this->assertCount(3, $array);
        $this->assertNotCount(5, $array);
        $this->assertEmpty([]);
        $this->assertNotEmpty($array);

        // Array has key
        $this->assertArrayHasKey('a', $array);
        $this->assertArrayNotHasKey('z', $array);

        // Contains value
        $this->assertContains(2, $array);
        $this->assertNotContains(10, $array);

        // Subset
        $this->assertArraySubset(['a' => 1], $array);

        // === String Assertions ===

        $string = 'Hello World';

        $this->assertStringContainsString('World', $string);
        $this->assertStringNotContainsString('Goodbye', $string);
        $this->assertStringContainsStringIgnoringCase('WORLD', $string);

        $this->assertStringStartsWith('Hello', $string);
        $this->assertStringEndsWith('World', $string);

        // Regex
        $this->assertMatchesRegularExpression('/\w+/', $string);
        $this->assertDoesNotMatchRegularExpression('/\d+/', $string);

        // String length
        $this->assertStringMatchesFormat('%s %s', $string);

        // === Numeric Assertions ===

        $value = 10;

        $this->assertGreaterThan(5, $value);
        $this->assertGreaterThanOrEqual(10, $value);
        $this->assertLessThan(15, $value);
        $this->assertLessThanOrEqual(10, $value);

        // Range
        $this->assertEqualsWithDelta(10.1, 10.2, 0.2); // Within delta

        // === File Assertions ===

        $this->assertFileExists(__FILE__);
        $this->assertFileDoesNotExist('/nonexistent');
        $this->assertFileIsReadable(__FILE__);
        $this->assertFileIsWritable(sys_get_temp_dir());

        // File content
        $this->assertFileEquals('/path/to/expected', '/path/to/actual');
        $this->assertStringEqualsFile(__FILE__, file_get_contents(__FILE__));

        // === JSON Assertions ===

        $json = '{"name": "John", "age": 30}';

        $this->assertJson($json);
        $this->assertJsonStringEqualsJsonString(
            '{"name":"John","age":30}',
            $json
        );

        $this->assertJsonStringEqualsJsonFile(
            '/path/to/expected.json',
            $json
        );

        // === Exception Assertions ===

        // Expect exception (must be called before code that throws)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input');
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/invalid/i');

        throw new \InvalidArgumentException('Invalid input', 400);
    }

    public function testCustomAssertions(): void
    {
        // You can create custom assertions
        $user = new User('john@example.com');

        $this->assertUserHasValidEmail($user);
    }

    private function assertUserHasValidEmail(User $user): void
    {
        $this->assertNotNull($user->getEmail(), 'User must have an email');
        $this->assertMatchesRegularExpression(
            '/^[^@]+@[^@]+$/',
            $user->getEmail(),
            'Email must be valid'
        );
    }
}
```

### Test Attributes (PHP 8+)

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Calculator::class)]
class AttributeExamplesTest extends TestCase
{
    #[Test]
    public function it_calculates_correctly(): void
    {
        // Using #[Test] instead of "test" prefix
    }

    #[Test]
    #[Group('math')]
    #[Group('calculator')]
    public function addition_works(): void
    {
        // Belongs to multiple groups
    }

    #[Test]
    #[DataProvider('priceProvider')]
    public function calculates_price(float $price, float $tax, float $expected): void
    {
        $result = $this->calculator->calculateWithTax($price, $tax);
        $this->assertEquals($expected, $result);
    }

    public static function priceProvider(): array
    {
        return [
            [100, 0.20, 120],
            [50, 0.10, 55],
        ];
    }
}
```

---

## PHPUnit Configuration

### Complete phpunit.xml.dist

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         requireCoverageMetadata="true"
         beStrictAboutCoverageMetadata="true"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true"
>
    <!-- PHP Configuration -->
    <php>
        <!-- Error reporting -->
        <ini name="display_errors" value="1" />
        <ini name="error_reporting" value="-1" />
        <ini name="memory_limit" value="512M" />

        <!-- Server variables -->
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
        <server name="SYMFONY_PHPUNIT_REMOVE" value="" />
        <server name="SYMFONY_PHPUNIT_VERSION" value="10.5" />

        <!-- Symfony Deprecations -->
        <env name="SYMFONY_DEPRECATIONS_HELPER" value="weak" />

        <!-- Database -->
        <env name="DATABASE_URL" value="sqlite:///:memory:" />

        <!-- Disable external services in tests -->
        <env name="MAILER_DSN" value="null://null" />
    </php>

    <!-- Test Suites -->
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="functional">
            <directory>tests/Functional</directory>
        </testsuite>
    </testsuites>

    <!-- Code Coverage -->
    <coverage processUncoveredFiles="true"
              pathCoverage="false"
              ignoreDeprecatedCodeUnits="true"
              disableCodeCoverageIgnore="false">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Entity</directory>
            <directory>src/DataFixtures</directory>
            <directory>src/Migrations</directory>
            <file>src/Kernel.php</file>
        </exclude>
        <report>
            <html outputDirectory="var/coverage" lowUpperBound="50" highLowerBound="80"/>
            <text outputFile="php://stdout" showUncoveredFiles="false"/>
            <clover outputFile="var/coverage/clover.xml"/>
        </report>
    </coverage>

    <!-- Listeners -->
    <extensions>
        <bootstrap class="Symfony\Bridge\PhpUnit\SymfonyTestsListener" />
    </extensions>
</phpunit>
```

### Environment-Specific Configuration

```yaml
# config/packages/test/framework.yaml
framework:
    test: true
    session:
        storage_factory_id: session.storage.factory.mock_file
    profiler:
        collect: false

# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        # Use SQLite in-memory for fast tests
        driver: 'pdo_sqlite'
        url: 'sqlite:///:memory:'
        charset: utf8mb4
```

---

## Unit Testing Services

### Simple Service Testing

```php
namespace App\Tests\Unit\Service;

use App\Service\SlugGenerator;
use PHPUnit\Framework\TestCase;

class SlugGeneratorTest extends TestCase
{
    private SlugGenerator $slugGenerator;

    protected function setUp(): void
    {
        $this->slugGenerator = new SlugGenerator();
    }

    public function testGenerateSlug(): void
    {
        $result = $this->slugGenerator->generate('Hello World');

        $this->assertEquals('hello-world', $result);
    }

    public function testGenerateSlugWithSpecialCharacters(): void
    {
        $result = $this->slugGenerator->generate('Hello & World!');

        $this->assertEquals('hello-and-world', $result);
    }

    public function testGenerateSlugWithUnicode(): void
    {
        $result = $this->slugGenerator->generate('Café François');

        $this->assertEquals('cafe-francois', $result);
    }

    public function testEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->slugGenerator->generate('');
    }
}
```

### Service with Dependencies

```php
namespace App\Service;

class OrderService
{
    public function __construct(
        private ProductRepository $productRepository,
        private PriceCalculator $priceCalculator,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {}

    public function createOrder(array $orderData): Order
    {
        $this->logger->info('Creating order', $orderData);

        $products = $this->productRepository->findByIds($orderData['product_ids']);

        $order = new Order();
        $order->setTotal($this->priceCalculator->calculateTotal($products));

        $this->mailer->send(/* order confirmation email */);

        return $order;
    }
}
```

```php
namespace App\Tests\Unit\Service;

use App\Repository\ProductRepository;
use App\Service\OrderService;
use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;

class OrderServiceTest extends TestCase
{
    public function testCreateOrder(): void
    {
        // Create mocks for all dependencies
        $productRepository = $this->createMock(ProductRepository::class);
        $priceCalculator = $this->createMock(PriceCalculator::class);
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Configure mocks
        $products = [new Product(), new Product()];

        $productRepository->expects($this->once())
            ->method('findByIds')
            ->with([1, 2])
            ->willReturn($products);

        $priceCalculator->expects($this->once())
            ->method('calculateTotal')
            ->with($products)
            ->willReturn(100.00);

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class));

        $logger->expects($this->once())
            ->method('info')
            ->with('Creating order', $this->anything());

        // Create service with mocks
        $orderService = new OrderService(
            $productRepository,
            $priceCalculator,
            $mailer,
            $logger
        );

        // Execute
        $order = $orderService->createOrder(['product_ids' => [1, 2]]);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(100.00, $order->getTotal());
    }
}
```

---

## Functional Testing with WebTestCase

### WebTestCase Fundamentals

`WebTestCase` boots the Symfony kernel and provides a test client for making HTTP requests.

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlogControllerTest extends WebTestCase
{
    public function testBlogIndex(): void
    {
        // Create client - boots the kernel
        $client = static::createClient();

        // Make GET request
        $crawler = $client->request('GET', '/blog');

        // Assert response
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Blog Posts');
    }
}
```

### Advanced Request Testing

```php
class AdvancedRequestTest extends WebTestCase
{
    public function testPostRequest(): void
    {
        $client = static::createClient();

        // POST request with parameters
        $client->request('POST', '/api/posts', [
            'title' => 'New Post',
            'content' => 'Content here',
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testJsonRequest(): void
    {
        $client = static::createClient();

        // JSON request
        $client->request(
            'POST',
            '/api/posts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'JSON Post',
                'content' => 'Content',
            ])
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Decode JSON response
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }

    public function testWithCustomHeaders(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/protected',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer token123',
                'HTTP_X-Custom-Header' => 'value',
            ]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testFileUpload(): void
    {
        $client = static::createClient();

        $photo = new UploadedFile(
            __DIR__.'/fixtures/photo.jpg',
            'photo.jpg',
            'image/jpeg'
        );

        $client->request('POST', '/upload', [], [
            'file' => $photo,
        ]);

        $this->assertResponseIsSuccessful();
    }
}
```

### Testing Authentication

```php
class AuthenticationTest extends WebTestCase
{
    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'user@example.com',
            '_password' => 'password',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/dashboard');
        $crawler = $client->followRedirect();

        $this->assertSelectorTextContains('.welcome', 'Welcome back');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Sign in')->form([
            '_username' => 'user@example.com',
            '_password' => 'wrong-password',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');
    }

    public function testProtectedPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertResponseRedirects('/login');
    }

    public function testLoginAsUser(): void
    {
        $client = static::createClient();

        // Get user from database
        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user@example.com']);

        // Login programmatically
        $client->loginUser($user);

        // Now can access protected pages
        $client->request('GET', '/profile');
        $this->assertResponseIsSuccessful();
    }
}
```

---

## KernelTestCase for Integration Tests

### Basic Integration Testing

```php
namespace App\Tests\Integration\Service;

use App\Service\ReportGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportGeneratorTest extends KernelTestCase
{
    private ReportGenerator $reportGenerator;

    protected function setUp(): void
    {
        // Boot the Symfony kernel
        self::bootKernel();

        // Get service from container
        $container = static::getContainer();
        $this->reportGenerator = $container->get(ReportGenerator::class);
    }

    public function testGenerateReport(): void
    {
        $report = $this->reportGenerator->generate('sales', [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
        ]);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertNotEmpty($report->getData());
    }
}
```

### Testing with Database

```php
namespace App\Tests\Integration\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->userRepository = $this->entityManager
            ->getRepository(User::class);
    }

    public function testFindActiveUsers(): void
    {
        $users = $this->userRepository->findBy(['active' => true]);

        $this->assertNotEmpty($users);
        foreach ($users as $user) {
            $this->assertTrue($user->isActive());
        }
    }

    public function testCreateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');
        $user->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());

        // Verify persistence
        $this->entityManager->clear();
        $found = $this->userRepository->find($user->getId());

        $this->assertInstanceOf(User::class, $found);
        $this->assertEquals('test@example.com', $found->getEmail());
    }

    public function testCustomQueryMethod(): void
    {
        $recentUsers = $this->userRepository->findRecentlyRegistered(30);

        $this->assertIsArray($recentUsers);
        foreach ($recentUsers as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertLessThanOrEqual(30, $user->getRegisteredAt()->diff(new \DateTime())->days);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

---

## The Test Client

### Client Configuration

```php
class ClientConfigurationTest extends WebTestCase
{
    public function testClientConfiguration(): void
    {
        // Create client with options
        $client = static::createClient([
            'environment' => 'test',
            'debug' => false,
        ]);

        // Server variables (HTTP headers, PHP settings)
        $client->setServerParameters([
            'HTTP_HOST' => 'example.com',
            'HTTP_USER_AGENT' => 'TestBot/1.0',
            'HTTPS' => 'on',
        ]);

        // Disable following redirects
        $client->followRedirects(false);

        // Enable following redirects
        $client->followRedirects(true);

        // Max redirects
        $client->setMaxRedirects(5);
    }

    public function testClientNavigation(): void
    {
        $client = static::createClient();

        // Make initial request
        $client->request('GET', '/page1');

        // Click a link
        $crawler = $client->clickLink('Next Page');

        // Go back
        $client->back();

        // Go forward
        $client->forward();

        // Reload current page
        $client->reload();

        // Request history
        $history = $client->getHistory();
        $this->assertCount(2, $history);

        // Clear history
        $client->restart();
    }

    public function testClientCookies(): void
    {
        $client = static::createClient();

        // Get cookie jar
        $cookieJar = $client->getCookieJar();

        // Set cookie
        $cookieJar->set(new Cookie('session_id', 'abc123'));

        // Get cookie
        $cookie = $cookieJar->get('session_id');
        $this->assertEquals('abc123', $cookie->getValue());

        // Clear cookies
        $cookieJar->clear();
    }

    public function testInternalRequest(): void
    {
        $client = static::createClient();

        // Get internal request object
        $client->request('GET', '/page');
        $request = $client->getRequest();

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/page', $request->getPathInfo());

        // Get internal response
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

---

## The Crawler

The Crawler is a powerful tool for traversing and extracting data from HTML/XML documents.

### Basic Traversal

```php
class CrawlerTraversalTest extends WebTestCase
{
    public function testCrawlerFiltering(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        // Filter by CSS selector
        $posts = $crawler->filter('.blog-post');
        $this->assertGreaterThan(0, $posts->count());

        // Filter by tag
        $links = $crawler->filter('a');

        // Filter by class
        $featured = $crawler->filter('.featured');

        // Filter by ID
        $header = $crawler->filter('#header');

        // Filter by attribute
        $externalLinks = $crawler->filter('a[target="_blank"]');

        // Nested filtering
        $postTitles = $crawler
            ->filter('.blog-post')
            ->filter('h2.title');

        // XPath filtering
        $crawler->filterXPath('//div[@class="post"]//h2');
    }

    public function testCrawlerExtraction(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog/post/1');

        // Get text content
        $title = $crawler->filter('h1.title')->text();
        $this->assertEquals('Blog Post Title', $title);

        // Get text with default
        $subtitle = $crawler->filter('.subtitle')->text('No subtitle');

        // Get HTML content
        $content = $crawler->filter('.post-content')->html();

        // Get attribute
        $link = $crawler->filter('a.read-more')->attr('href');
        $this->assertEquals('/blog/post/1', $link);

        // Get attribute with default
        $target = $crawler->filter('a')->attr('target', '_self');
    }

    public function testCrawlerIteration(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog');

        // Extract multiple elements
        $titles = $crawler->filter('.blog-post h2')->each(function ($node) {
            return $node->text();
        });

        $this->assertIsArray($titles);
        $this->assertNotEmpty($titles);

        // Extract with more complex logic
        $posts = $crawler->filter('.blog-post')->each(function ($node) {
            return [
                'title' => $node->filter('h2')->text(),
                'author' => $node->filter('.author')->text(),
                'date' => $node->filter('.date')->text(),
            ];
        });

        $this->assertArrayHasKey('title', $posts[0]);
    }

    public function testCrawlerNavigation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/page');

        // First element
        $first = $crawler->filter('.item')->first();

        // Last element
        $last = $crawler->filter('.item')->last();

        // Nth element (0-indexed)
        $third = $crawler->filter('.item')->eq(2);

        // Slice
        $firstThree = $crawler->filter('.item')->slice(0, 3);

        // Parent
        $parent = $crawler->filter('.child')->parents();

        // Children
        $children = $crawler->filter('.parent')->children();

        // Siblings
        $siblings = $crawler->filter('.item')->siblings();

        // Next sibling
        $next = $crawler->filter('.item')->nextAll();

        // Previous sibling
        $previous = $crawler->filter('.item')->previousAll();
    }
}
```

### Working with Links and Forms

```php
class LinksAndFormsTest extends WebTestCase
{
    public function testClickLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        // Select link by text
        $link = $crawler->selectLink('About Us')->link();
        $client->click($link);

        $this->assertResponseIsSuccessful();

        // Select link by partial text
        $link = $crawler->selectLink('Contact')->link();

        // Get link info
        $uri = $link->getUri();
        $method = $link->getMethod();
    }

    public function testSubmitForms(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Select form by button text
        $form = $crawler->selectButton('Send Message')->form();

        // Set form values
        $form['contact[name]'] = 'John Doe';
        $form['contact[email]'] = 'john@example.com';
        $form['contact[message]'] = 'Hello!';

        // Submit form
        $client->submit($form);

        $this->assertResponseRedirects();
    }

    public function testFormManipulation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/form');

        $form = $crawler->selectButton('Submit')->form();

        // Get form values
        $values = $form->getValues();

        // Set multiple values
        $form->setValues([
            'user[name]' => 'John',
            'user[email]' => 'john@example.com',
        ]);

        // Disable validation
        $form->disableValidation();

        // Select/deselect checkbox
        $form['terms']->tick();
        $form['terms']->untick();

        // Select radio button
        $form['gender']->select('male');

        // Select dropdown option
        $form['country']->select('US');

        // Upload file
        $form['avatar']->upload('/path/to/file.jpg');

        $client->submit($form);
    }
}
```

---

## Making Requests and Asserting Responses

### Request Methods

```php
class RequestMethodsTest extends WebTestCase
{
    public function testGetRequest(): void
    {
        $client = static::createClient();

        $crawler = $client->request(
            'GET',                    // HTTP method
            '/posts',                 // URI
            ['page' => 2],           // Parameters (query string for GET)
            [],                       // Files
            ['CONTENT_TYPE' => 'text/html'] // Server vars/headers
        );

        $this->assertResponseIsSuccessful();
    }

    public function testPostRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/posts',
            [                         // POST parameters
                'title' => 'New Post',
                'content' => 'Content',
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testPutRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/posts/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Updated Title'])
        );

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteRequest(): void
    {
        $client = static::createClient();

        $client->request('DELETE', '/posts/1');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testPatchRequest(): void
    {
        $client = static::createClient();

        $client->request(
            'PATCH',
            '/posts/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['published' => true])
        );

        $this->assertResponseIsSuccessful();
    }
}
```

### Response Assertions

```php
class ResponseAssertionsTest extends WebTestCase
{
    public function testStatusCodeAssertions(): void
    {
        $client = static::createClient();

        // Successful responses (2xx)
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        // Redirect responses (3xx)
        $client->request('GET', '/old-page');
        $this->assertResponseRedirects();
        $this->assertResponseRedirects('/new-page');
        $this->assertResponseRedirects('/new-page', 301);

        // Client errors (4xx)
        $client->request('GET', '/not-found');
        $this->assertResponseStatusCodeSame(404);

        // Server errors (5xx)
        $client->request('GET', '/error');
        $this->assertResponseStatusCodeSame(500);

        // Specific status code
        $client->request('POST', '/api/invalid');
        $this->assertResponseStatusCodeSame(422); // Unprocessable Entity
    }

    public function testHeaderAssertions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/data');

        // Header exists
        $this->assertResponseHasHeader('Content-Type');

        // Header has specific value
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Header doesn't have value
        $this->assertResponseHeaderNotSame('Content-Type', 'text/html');

        // Multiple header checks
        $response = $client->getResponse();
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertEquals('no-cache', $response->headers->get('X-Custom-Header'));
    }

    public function testContentAssertions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/page');

        // Check if selector exists
        $this->assertSelectorExists('h1');
        $this->assertSelectorNotExists('.missing-class');

        // Check selector text
        $this->assertSelectorTextContains('h1', 'Welcome');
        $this->assertSelectorTextSame('h1', 'Welcome to our site');

        // Check selector count
        $this->assertCount(5, $client->getCrawler()->filter('.item'));

        // Check input values
        $this->assertInputValueSame('username', 'john');
        $this->assertInputValueNotSame('password', '');

        // Check checkboxes
        $this->assertCheckboxChecked('terms');
        $this->assertCheckboxNotChecked('newsletter');

        // Check page title
        $this->assertPageTitleSame('Home Page');
        $this->assertPageTitleContains('Home');
    }

    public function testRouteAssertions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts/1');

        // Check current route
        $this->assertRouteSame('post_show');

        // Check route with parameters
        $this->assertRouteSame('post_show', ['id' => '1']);
    }

    public function testJsonResponseAssertions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/posts');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('posts', $data);
        $this->assertCount(10, $data['posts']);
    }
}
```

---

## Testing Forms

### Form Submission Testing

```php
namespace App\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRegistrationTest extends WebTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="registration"]');

        // Fill and submit form
        $form = $crawler->selectButton('Register')->form([
            'registration[email]' => 'newuser@example.com',
            'registration[plainPassword][first]' => 'SecurePass123',
            'registration[plainPassword][second]' => 'SecurePass123',
            'registration[agreeTerms]' => true,
        ]);

        $client->submit($form);

        // Should redirect to success page
        $this->assertResponseRedirects('/registration/success');

        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Registration successful');
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Submit with invalid data
        $form = $crawler->selectButton('Register')->form([
            'registration[email]' => 'invalid-email',  // Invalid email
            'registration[plainPassword][first]' => '123',  // Too short
            'registration[plainPassword][second]' => '456',  // Doesn't match
            'registration[agreeTerms]' => false,  // Not accepted
        ]);

        $client->submit($form);

        // Should re-display form with errors
        $this->assertResponseIsUnprocessable(); // 422

        $this->assertSelectorExists('.invalid-feedback');

        // Check specific errors
        $this->assertSelectorTextContains(
            '#registration_email ~ .invalid-feedback',
            'email'
        );

        $this->assertSelectorTextContains(
            '#registration_plainPassword_first ~ .invalid-feedback',
            'at least'
        );
    }

    public function testCsrfProtection(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form();

        // Tamper with CSRF token
        $form['registration[_token]'] = 'invalid-token';

        $client->submit($form);

        $this->assertResponseStatusCodeSame(400); // Or 403
    }
}
```

### Testing Form Types Directly

```php
namespace App\Tests\Unit\Form;

use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ProductTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Test Product',
            'price' => 29.99,
            'description' => 'A great product',
            'active' => true,
        ];

        $model = new Product();
        $form = $this->factory->create(ProductType::class, $model);

        $expected = new Product();
        $expected->setName('Test Product');
        $expected->setPrice(29.99);
        $expected->setDescription('A great product');
        $expected->setActive(true);

        // Submit form data
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $model);

        // Check view
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(ProductType::class);

        // Check form has expected fields
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('price'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('active'));

        // Check field types
        $this->assertInstanceOf(
            TextType::class,
            $form->get('name')->getConfig()->getType()->getInnerType()
        );

        $this->assertInstanceOf(
            MoneyType::class,
            $form->get('price')->getConfig()->getType()->getInnerType()
        );
    }

    public function testCustomFormOption(): void
    {
        $form = $this->factory->create(ProductType::class, null, [
            'include_description' => false,
        ]);

        $this->assertFalse($form->has('description'));
    }
}
```

---

## Testing with Databases

### Database Test Strategies

#### Strategy 1: Test Database with Fixtures

```php
namespace App\Tests\Integration;

use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->userRepository = $this->entityManager
            ->getRepository(User::class);

        // Load fixtures
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $loader = new Loader();
        $loader->addFixture(new UserFixtures());

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testFindActiveUsers(): void
    {
        $users = $this->userRepository->findBy(['active' => true]);

        $this->assertGreaterThan(0, count($users));
        foreach ($users as $user) {
            $this->assertTrue($user->isActive());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

#### Strategy 2: Database Transactions

```php
namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;
    private Connection $connection;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->connection = $this->entityManager->getConnection();

        // Start transaction
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback all changes
        $this->connection->rollBack();

        $this->entityManager->close();
        parent::tearDown();
    }
}

class UserServiceTest extends DatabaseTestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());

        // Changes will be rolled back in tearDown
    }
}
```

#### Strategy 3: In-Memory SQLite Database

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        driver: 'pdo_sqlite'
        url: 'sqlite:///:memory:'
        charset: utf8mb4
```

```php
namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class SqliteTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Create database schema
        $this->createSchema();
    }

    private function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager
            ->getMetadataFactory()
            ->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

### Testing Repositories

```php
namespace App\Tests\Integration\Repository;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostRepositoryTest extends KernelTestCase
{
    private PostRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->repository = $kernel->getContainer()
            ->get(PostRepository::class);
    }

    public function testFindPublishedPosts(): void
    {
        $posts = $this->repository->findPublished();

        $this->assertNotEmpty($posts);

        foreach ($posts as $post) {
            $this->assertTrue($post->isPublished());
            $this->assertInstanceOf(\DateTimeInterface::class, $post->getPublishedAt());
        }
    }

    public function testFindByAuthor(): void
    {
        $posts = $this->repository->findByAuthor('john-doe');

        $this->assertNotEmpty($posts);

        foreach ($posts as $post) {
            $this->assertEquals('john-doe', $post->getAuthor()->getSlug());
        }
    }

    public function testSearchPosts(): void
    {
        $posts = $this->repository->search('symfony');

        $this->assertNotEmpty($posts);

        foreach ($posts as $post) {
            $content = strtolower($post->getTitle() . ' ' . $post->getContent());
            $this->assertStringContainsString('symfony', $content);
        }
    }

    public function testFindWithPagination(): void
    {
        $page = 1;
        $limit = 10;

        $posts = $this->repository->findPaginated($page, $limit);

        $this->assertLessThanOrEqual($limit, count($posts));
    }
}
```

---

## Mocking Services and Dependencies

### Creating Mocks

```php
namespace App\Tests\Unit\Service;

use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function testGetProduct(): void
    {
        // Create mock repository
        $repository = $this->createMock(ProductRepository::class);

        // Set expectations
        $product = new Product();
        $product->setId(1);
        $product->setName('Test Product');

        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        // Create service with mock
        $service = new ProductService($repository);

        // Test
        $result = $service->getProduct(1);

        $this->assertSame($product, $result);
        $this->assertEquals('Test Product', $result->getName());
    }

    public function testGetProductNotFound(): void
    {
        $repository = $this->createMock(ProductRepository::class);

        $repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $service = new ProductService($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Product not found');

        $service->getProduct(999);
    }
}
```

### Advanced Mock Configurations

```php
class AdvancedMockingTest extends TestCase
{
    public function testMultipleCalls(): void
    {
        $repository = $this->createMock(UserRepository::class);

        // Expect exactly 3 calls
        $repository->expects($this->exactly(3))
            ->method('save');

        $service = new UserService($repository);
        $service->saveMultiple([$user1, $user2, $user3]);
    }

    public function testConditionalReturns(): void
    {
        $repository = $this->createMock(UserRepository::class);

        // Return different values based on input
        $repository->method('find')
            ->willReturnCallback(function ($id) {
                if ($id === 1) {
                    return new User('john@example.com');
                }
                if ($id === 2) {
                    return new User('jane@example.com');
                }
                return null;
            });

        $service = new UserService($repository);

        $user1 = $service->getUser(1);
        $this->assertEquals('john@example.com', $user1->getEmail());

        $user2 = $service->getUser(2);
        $this->assertEquals('jane@example.com', $user2->getEmail());

        $user3 = $service->getUser(3);
        $this->assertNull($user3);
    }

    public function testConsecutiveCalls(): void
    {
        $repository = $this->createMock(CacheRepository::class);

        // Return different values on consecutive calls
        $repository->method('get')
            ->willReturnOnConsecutiveCalls(
                null,        // First call: cache miss
                'cached',    // Second call: cache hit
                'cached'     // Third call: cache hit
            );

        $service = new CacheService($repository);

        $this->assertNull($service->get('key'));      // Cache miss
        $this->assertEquals('cached', $service->get('key')); // Cache hit
        $this->assertEquals('cached', $service->get('key')); // Cache hit
    }

    public function testMethodChaining(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Test code that uses method chaining
        $result = $queryBuilder
            ->select('u')
            ->from('User', 'u')
            ->where('u.active = :active')
            ->setParameter('active', true)
            ->getQuery();
    }

    public function testVerifyMethodArguments(): void
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                return $email->getTo()[0]->getAddress() === 'user@example.com'
                    && $email->getSubject() === 'Welcome';
            }));

        $service = new NotificationService($mailer);
        $service->sendWelcomeEmail('user@example.com');
    }
}
```

### Stubs vs Mocks

```php
class StubsVsMocksTest extends TestCase
{
    /**
     * Stub: Just returns values, no behavioral verification
     */
    public function testWithStub(): void
    {
        // Create stub
        $stub = $this->createStub(MailerInterface::class);

        // Configure return value (no expectations)
        $stub->method('send')->willReturn(true);

        $service = new NotificationService($stub);
        $result = $service->notify($user);

        // We don't verify if send() was called
        $this->assertTrue($result);
    }

    /**
     * Mock: Verifies behavior and method calls
     */
    public function testWithMock(): void
    {
        // Create mock
        $mock = $this->createMock(MailerInterface::class);

        // Set expectations (must be called once)
        $mock->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(Email::class))
            ->willReturn(true);

        $service = new NotificationService($mock);
        $service->notify($user);

        // Mock verifies send() was called once in tearDown
    }

    /**
     * Partial Mock: Mock some methods, keep others real
     */
    public function testPartialMock(): void
    {
        $service = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['validateCard'])
            ->getMock();

        $service->method('validateCard')->willReturn(true);

        // validateCard is mocked, other methods are real
        $result = $service->processPayment($paymentData);

        $this->assertTrue($result);
    }
}
```

### Mocking in Functional Tests

```php
class MockedServiceInFunctionalTest extends WebTestCase
{
    public function testWithMockedEmailService(): void
    {
        $client = static::createClient();

        // Create mock
        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())
            ->method('sendConfirmation')
            ->willReturn(true);

        // Replace service in container
        $client->getContainer()->set(EmailService::class, $emailService);

        // Make request that uses EmailService
        $client->request('POST', '/orders', [
            'product_id' => 1,
            'quantity' => 2,
        ]);

        $this->assertResponseIsSuccessful();

        // Mock expectation is verified
    }
}
```

---

## PHPUnit Bridge and Deprecations

### Understanding Deprecations

The PHPUnit Bridge helps manage deprecations in Symfony applications.

```bash
# Install PHPUnit Bridge
composer require --dev symfony/phpunit-bridge
```

### Deprecation Modes

```xml
<!-- phpunit.xml.dist -->
<php>
    <!-- Modes:
         - "disabled": Don't track deprecations
         - "weak": Show summary but don't fail
         - "weak_vendors": Weak mode for vendor deprecations only
         - 0: Fail if any deprecations
         - max[self]=X: Fail if more than X self deprecations
         - max[direct]=X: Fail if more than X direct deprecations
         - max[indirect]=X: Fail if more than X indirect deprecations
    -->
    <env name="SYMFONY_DEPRECATIONS_HELPER" value="max[self]=0&max[direct]=0" />
</php>
```

### Working with Deprecations

```php
namespace App\Tests\Unit;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;

class DeprecationTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testDeprecatedMethod(): void
    {
        $this->expectDeprecation('Using OldClass::oldMethod() is deprecated since 2.0, use NewClass::newMethod() instead.');

        $obj = new OldClass();
        $obj->oldMethod(); // Triggers deprecation
    }

    /**
     * @group legacy
     */
    public function testMultipleDeprecations(): void
    {
        $this->expectDeprecation('First deprecation message');
        $this->expectDeprecation('Second deprecation message');

        // Code that triggers both deprecations
    }
}
```

### Triggering Deprecations

```php
namespace App\Service;

use Symfony\Component\ErrorHandler\DebugClassLoader;

class LegacyService
{
    /**
     * @deprecated since 2.0, use newMethod() instead
     */
    public function oldMethod(): void
    {
        trigger_deprecation(
            'vendor/package-name',
            '2.0',
            'Using %s() is deprecated, use %s() instead.',
            __METHOD__,
            'newMethod'
        );

        // Old implementation
    }

    public function newMethod(): void
    {
        // New implementation
    }
}
```

---

## Test Doubles

### Types of Test Doubles

```php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class TestDoublesExamplesTest extends TestCase
{
    /**
     * Dummy: Object that is passed but never used
     */
    public function testWithDummy(): void
    {
        $dummy = $this->createStub(LoggerInterface::class);

        // Logger is required but not used in this test
        $service = new SimpleService($dummy);
        $result = $service->calculate(10);

        $this->assertEquals(20, $result);
    }

    /**
     * Stub: Returns pre-configured values
     */
    public function testWithStub(): void
    {
        $stub = $this->createStub(ConfigRepository::class);
        $stub->method('get')->willReturn('test-value');

        $service = new ConfigService($stub);
        $value = $service->getConfig('key');

        $this->assertEquals('test-value', $value);
    }

    /**
     * Spy: Records how it was called
     */
    public function testWithSpy(): void
    {
        $spy = $this->createMock(AuditLogger::class);

        $spy->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('user_created'),
                $this->arrayHasKey('user_id')
            );

        $service = new UserService($spy);
        $service->createUser(['email' => 'user@example.com']);

        // Spy verified the call
    }

    /**
     * Mock: Pre-programmed with expectations
     */
    public function testWithMock(): void
    {
        $mock = $this->createMock(PaymentGateway::class);

        $mock->expects($this->once())
            ->method('charge')
            ->with(100.00, 'USD')
            ->willReturn(new ChargeResult(true, 'txn_123'));

        $service = new PaymentService($mock);
        $result = $service->processPayment(100.00);

        $this->assertTrue($result->isSuccessful());
    }

    /**
     * Fake: Working implementation but simplified
     */
    public function testWithFake(): void
    {
        // In-memory repository instead of database
        $fake = new InMemoryUserRepository();
        $fake->add(new User('user@example.com'));

        $service = new UserService($fake);
        $user = $service->findByEmail('user@example.com');

        $this->assertInstanceOf(User::class, $user);
    }
}

/**
 * Example Fake Implementation
 */
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];

    public function add(User $user): void
    {
        $this->users[$user->getEmail()] = $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->users[$email] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }
}
```

---

## Code Coverage

### Generating Coverage Reports

```bash
# HTML report (most detailed)
php bin/phpunit --coverage-html var/coverage

# Text report (console output)
php bin/phpunit --coverage-text

# Clover XML (for CI tools)
php bin/phpunit --coverage-clover var/coverage/clover.xml

# Coverage with minimum threshold
php bin/phpunit --coverage-text --coverage-filter=src --coverage-min=80
```

### Coverage Configuration

```xml
<!-- phpunit.xml.dist -->
<coverage processUncoveredFiles="true"
          pathCoverage="false"
          ignoreDeprecatedCodeUnits="true">
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <exclude>
        <directory>src/Entity</directory>
        <directory>src/DataFixtures</directory>
        <directory>src/Migrations</directory>
        <file>src/Kernel.php</file>
    </exclude>
    <report>
        <html outputDirectory="var/coverage" lowUpperBound="50" highLowerBound="80"/>
        <text outputFile="php://stdout" showUncoveredFiles="false"/>
        <clover outputFile="var/coverage/clover.xml"/>
    </report>
</coverage>
```

### Coverage Annotations

```php
namespace App\Service;

/**
 * @codeCoverageIgnore
 */
class DebugService
{
    // Entire class excluded from coverage
}

class PaymentService
{
    public function processPayment(array $data): bool
    {
        // Covered code
        $this->validate($data);

        // @codeCoverageIgnoreStart
        if (DEBUG_MODE) {
            $this->logDebugInfo($data);
        }
        // @codeCoverageIgnoreEnd

        return $this->charge($data);
    }

    /**
     * @codeCoverageIgnore
     */
    private function logDebugInfo(array $data): void
    {
        // Not included in coverage
    }
}
```

### Interpreting Coverage

```
Code Coverage Report:
  2024-01-15 10:30:00

 Summary:
  Classes: 85.00% (17/20)
  Methods: 82.35% (42/51)
  Lines:   78.92% (329/417)

 App\Service\UserService
  Methods:  100.00% ( 5/ 5)
  Lines:     95.00% (19/20)

 App\Controller\PostController
  Methods:   66.67% ( 4/ 6)
  Lines:     72.00% (36/50)
```

Coverage Metrics:
- **80%+**: Good coverage
- **60-80%**: Acceptable
- **<60%**: Needs improvement

Focus on:
- Critical business logic
- Complex algorithms
- Edge cases

---

## Data Providers

### Basic Data Providers

```php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function testAdd(int $a, int $b, int $expected): void
    {
        $calculator = new Calculator();
        $result = $calculator->add($a, $b);

        $this->assertEquals($expected, $result);
    }

    public static function additionProvider(): array
    {
        return [
            'positive numbers' => [1, 2, 3],
            'negative numbers' => [-1, -2, -3],
            'mixed' => [1, -1, 0],
            'zeros' => [0, 0, 0],
            'large numbers' => [1000, 2000, 3000],
        ];
    }
}
```

### Using Generators for Data Providers

```php
class GeneratorDataProviderTest extends TestCase
{
    /**
     * @dataProvider emailProvider
     */
    public function testEmailValidation(string $email, bool $isValid): void
    {
        $validator = new EmailValidator();
        $result = $validator->isValid($email);

        $this->assertEquals($isValid, $result);
    }

    public static function emailProvider(): iterable
    {
        yield 'valid simple email' => ['user@example.com', true];
        yield 'valid with subdomain' => ['user@mail.example.com', true];
        yield 'valid with plus' => ['user+tag@example.com', true];
        yield 'invalid no at sign' => ['userexample.com', false];
        yield 'invalid no domain' => ['user@', false];
        yield 'invalid spaces' => ['user @example.com', false];
    }
}
```

### Complex Data Providers

```php
class ComplexDataProviderTest extends TestCase
{
    /**
     * @dataProvider orderProvider
     */
    public function testOrderTotal(array $items, float $tax, float $expected): void
    {
        $order = new Order($items);
        $total = $order->calculateTotal($tax);

        $this->assertEquals($expected, $total);
    }

    public static function orderProvider(): array
    {
        return [
            'single item no tax' => [
                [['price' => 10.00, 'quantity' => 1]],
                0.00,
                10.00
            ],
            'multiple items no tax' => [
                [
                    ['price' => 10.00, 'quantity' => 2],
                    ['price' => 5.00, 'quantity' => 3],
                ],
                0.00,
                35.00
            ],
            'with tax' => [
                [['price' => 100.00, 'quantity' => 1]],
                0.20,
                120.00
            ],
        ];
    }
}
```

### Combining Multiple Data Providers

```php
class MultipleProvidersTest extends TestCase
{
    /**
     * @dataProvider validPasswordProvider
     * @dataProvider invalidPasswordProvider
     */
    public function testPasswordValidation(string $password, bool $expected): void
    {
        $validator = new PasswordValidator();
        $result = $validator->isValid($password);

        $this->assertEquals($expected, $result);
    }

    public static function validPasswordProvider(): array
    {
        return [
            'strong password' => ['SecureP@ssw0rd', true],
            'with numbers' => ['Password123!', true],
        ];
    }

    public static function invalidPasswordProvider(): array
    {
        return [
            'too short' => ['Pass1!', false],
            'no special char' => ['Password123', false],
            'no number' => ['Password!', false],
        ];
    }
}
```

### Data Provider with Dependencies

```php
class DataProviderWithDependenciesTest extends TestCase
{
    /**
     * @dataProvider userDataProvider
     */
    public function testUserCreation(array $userData, string $expectedRole): void
    {
        $user = User::fromArray($userData);

        $this->assertEquals($expectedRole, $user->getRole());
    }

    public static function userDataProvider(): array
    {
        return [
            'admin user' => [
                ['email' => 'admin@example.com', 'is_admin' => true],
                'ROLE_ADMIN'
            ],
            'regular user' => [
                ['email' => 'user@example.com', 'is_admin' => false],
                'ROLE_USER'
            ],
        ];
    }
}
```

---

## Best Practices

### 1. Test Naming Conventions

```php
// GOOD: Descriptive test names
public function testUserCannotRegisterWithInvalidEmail(): void
public function testCalculatorReturnsZeroForEmptyArray(): void
public function testOrderTotalIncludesTaxWhenApplicable(): void

// BAD: Vague test names
public function testRegister(): void
public function testCalculate(): void
public function testTotal(): void
```

### 2. Arrange-Act-Assert Pattern

```php
public function testCreateOrder(): void
{
    // Arrange: Set up test data and dependencies
    $customer = new Customer('John Doe');
    $product = new Product('Widget', 10.00);
    $service = new OrderService($this->repository);

    // Act: Execute the operation
    $order = $service->createOrder($customer, [$product]);

    // Assert: Verify the results
    $this->assertInstanceOf(Order::class, $order);
    $this->assertEquals($customer, $order->getCustomer());
    $this->assertCount(1, $order->getItems());
    $this->assertEquals(10.00, $order->getTotal());
}
```

### 3. Test Independence

```php
// GOOD: Each test is independent
class GoodTestCase extends TestCase
{
    public function testFirst(): void
    {
        $service = new Service();
        $service->doSomething();
        $this->assertTrue($service->isDone());
    }

    public function testSecond(): void
    {
        $service = new Service(); // Fresh instance
        $service->doSomethingElse();
        $this->assertFalse($service->isDone());
    }
}

// BAD: Tests depend on execution order
class BadTestCase extends TestCase
{
    private $service;

    public function testFirst(): void
    {
        $this->service = new Service();
        $this->service->setValue(10);
    }

    public function testSecond(): void
    {
        // Depends on testFirst running first!
        $this->assertEquals(10, $this->service->getValue());
    }
}
```

### 4. One Assert Per Test (Generally)

```php
// GOOD: Focused test
public function testUserEmailIsSet(): void
{
    $user = new User('john@example.com');
    $this->assertEquals('john@example.com', $user->getEmail());
}

public function testUserIsActiveByDefault(): void
{
    $user = new User('john@example.com');
    $this->assertTrue($user->isActive());
}

// ACCEPTABLE: Multiple asserts for same concept
public function testUserCreation(): void
{
    $user = new User('john@example.com');

    $this->assertEquals('john@example.com', $user->getEmail());
    $this->assertNull($user->getName()); // Not set yet
    $this->assertTrue($user->isActive()); // Active by default
}

// BAD: Testing multiple unrelated things
public function testEverything(): void
{
    $user = new User('john@example.com');
    $this->assertEquals('john@example.com', $user->getEmail());

    $product = new Product('Widget');
    $this->assertEquals('Widget', $product->getName());

    $order = new Order();
    $this->assertEmpty($order->getItems());
}
```

### 5. Don't Test Framework Code

```php
// BAD: Testing Symfony's functionality
public function testSymfonyRouting(): void
{
    $this->assertEquals('/posts/1', $this->router->generate('post_show', ['id' => 1]));
}

// GOOD: Test your business logic
public function testFindPublishedPosts(): void
{
    $posts = $this->repository->findPublished();

    foreach ($posts as $post) {
        $this->assertTrue($post->isPublished());
    }
}
```

---

This concludes the comprehensive testing concepts guide. Practice these patterns and techniques to build a robust test suite for your Symfony applications!
