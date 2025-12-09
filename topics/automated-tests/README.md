# Automated Tests

Master testing in Symfony applications with PHPUnit, from unit tests to functional tests.

---

## Learning Objectives

After completing this topic, you will be able to:

- Understand the testing pyramid and different test types
- Write unit tests for services and business logic
- Create functional tests for controllers and HTTP endpoints
- Use integration tests with KernelTestCase
- Mock dependencies and services
- Test with databases using fixtures and transactions
- Generate and analyze code coverage
- Handle deprecations with PHPUnit Bridge
- Test forms, authentication, and security

---

## Prerequisites

- Symfony Architecture basics
- PHPUnit fundamentals
- Dependency Injection understanding
- Doctrine ORM basics (for integration tests)

---

## Topics Covered

1. [Testing Pyramid](#1-testing-pyramid)
2. [PHPUnit Configuration](#2-phpunit-configuration)
3. [Unit Tests](#3-unit-tests)
4. [Functional Tests](#4-functional-tests)
5. [Integration Tests](#5-integration-tests)
6. [Test Client and Crawler](#6-test-client-and-crawler)
7. [Testing Forms](#7-testing-forms)
8. [Database Testing](#8-database-testing)
9. [Mocking and Test Doubles](#9-mocking-and-test-doubles)
10. [PHPUnit Bridge](#10-phpunit-bridge)
11. [Code Coverage](#11-code-coverage)
12. [Data Providers](#12-data-providers)

---

## 1. Testing Pyramid

### Test Types Overview

```
                    ▲
                   / \
                  /   \
                 /  E2E \          Few - Slow - Expensive
                /_________\
               /           \
              / Functional  \     Some - Medium - Moderate
             /_______________\
            /                 \
           /   Integration     \  More - Faster - Cheaper
          /____________________\
         /                      \
        /       Unit Tests       \  Most - Fastest - Cheapest
       /__________________________\
```

### Test Type Comparison

| Type | Scope | Speed | Complexity | Confidence |
|------|-------|-------|------------|------------|
| Unit | Single class/method | Fastest | Low | Low |
| Integration | Multiple components | Fast | Medium | Medium |
| Functional | HTTP endpoints | Medium | Medium | High |
| E2E | Full application | Slowest | High | Highest |

### When to Use Each Type

```php
// Unit Test - Test isolated business logic
class PriceCalculatorTest extends TestCase
{
    public function testCalculateWithTax(): void
    {
        $calculator = new PriceCalculator();
        $result = $calculator->calculateWithTax(100, 0.20);

        $this->assertEquals(120, $result);
    }
}

// Integration Test - Test service with dependencies
class OrderServiceTest extends KernelTestCase
{
    public function testCreateOrder(): void
    {
        $container = static::getContainer();
        $orderService = $container->get(OrderService::class);

        $order = $orderService->createOrder($data);

        $this->assertInstanceOf(Order::class, $order);
    }
}

// Functional Test - Test HTTP endpoints
class PostControllerTest extends WebTestCase
{
    public function testShowPost(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Post Title');
    }
}
```

---

## 2. PHPUnit Configuration

### Installation

```bash
# Install PHPUnit and Symfony test components
composer require --dev phpunit/phpunit symfony/test-pack

# PHPUnit configuration is created automatically
# phpunit.xml.dist
```

### PHPUnit Configuration File

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!-- phpunit.xml.dist -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true"
>
    <php>
        <ini name="display_errors" value="1" />
        <ini name="error_reporting" value="-1" />
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
        <server name="SYMFONY_PHPUNIT_REMOVE" value="" />
        <server name="SYMFONY_PHPUNIT_VERSION" value="10.5" />
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Entity</directory>
            <directory>src/Kernel.php</directory>
        </exclude>
    </coverage>

    <listeners>
        <listener class="Symfony\Bridge\PhpUnit\SymfonyTestsListener" />
    </listeners>
</phpunit>
```

### Test Bootstrap

```php
// tests/bootstrap.php
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
```

### Running Tests

```bash
# Run all tests
php bin/phpunit

# Run specific test file
php bin/phpunit tests/Unit/Service/PriceCalculatorTest.php

# Run specific test method
php bin/phpunit --filter testCalculateWithTax

# Run tests with coverage
php bin/phpunit --coverage-html var/coverage

# Run tests with testdox (readable output)
php bin/phpunit --testdox

# Run specific test suite
php bin/phpunit --testsuite unit
```

---

## 3. Unit Tests

### Basic Unit Test

```php
namespace App\Tests\Unit\Service;

use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
    }

    public function testCalculateWithTax(): void
    {
        $result = $this->calculator->calculateWithTax(100, 0.20);

        $this->assertEquals(120, $result);
    }

    public function testCalculateDiscount(): void
    {
        $result = $this->calculator->calculateDiscount(100, 10);

        $this->assertEquals(90, $result);
    }

    public function testNegativePriceThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');

        $this->calculator->calculateWithTax(-100, 0.20);
    }
}
```

### Testing with Dependencies

```php
namespace App\Tests\Unit\Service;

use App\Repository\ProductRepository;
use App\Service\ProductService;
use PHPUnit\Framework\TestCase;

class ProductServiceTest extends TestCase
{
    public function testGetActiveProducts(): void
    {
        // Create mock repository
        $repository = $this->createMock(ProductRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([/* products */]);

        $service = new ProductService($repository);
        $products = $service->getActiveProducts();

        $this->assertIsArray($products);
    }
}
```

### Common Assertions

```php
class AssertionExamplesTest extends TestCase
{
    public function testAssertions(): void
    {
        // Equality
        $this->assertEquals(10, $result);
        $this->assertSame($obj1, $obj2); // Strict comparison
        $this->assertNotEquals(5, $result);

        // Boolean
        $this->assertTrue($condition);
        $this->assertFalse($condition);

        // Null
        $this->assertNull($value);
        $this->assertNotNull($value);

        // Types
        $this->assertIsString($value);
        $this->assertIsInt($value);
        $this->assertIsArray($value);
        $this->assertIsBool($value);
        $this->assertInstanceOf(User::class, $user);

        // Arrays
        $this->assertCount(5, $array);
        $this->assertEmpty($array);
        $this->assertNotEmpty($array);
        $this->assertContains('value', $array);
        $this->assertArrayHasKey('key', $array);

        // Strings
        $this->assertStringContainsString('hello', $text);
        $this->assertStringStartsWith('Hello', $text);
        $this->assertStringEndsWith('world', $text);
        $this->assertMatchesRegularExpression('/\d+/', $text);

        // Exceptions
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input');
        $this->expectExceptionCode(400);

        // Files
        $this->assertFileExists('/path/to/file');
        $this->assertFileIsReadable('/path/to/file');

        // Greater/Less than
        $this->assertGreaterThan(5, $value);
        $this->assertLessThan(10, $value);
        $this->assertGreaterThanOrEqual(5, $value);
    }
}
```

---

## 4. Functional Tests

### Basic Functional Test

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testIndexPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Posts');
    }

    public function testShowPost(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/posts/1');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.post-content');
        $this->assertCount(1, $crawler->filter('h1'));
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();

        // Submit form
        $client->request('POST', '/posts/create', [
            'title' => 'New Post',
            'content' => 'Post content',
        ]);

        $this->assertResponseRedirects();

        // Follow redirect
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Post created');
    }

    public function testPostNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts/99999');

        $this->assertResponseStatusCodeSame(404);
    }
}
```

### Testing JSON APIs

```php
namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostApiTest extends WebTestCase
{
    public function testGetPosts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/posts', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testCreatePost(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/posts', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'API Post',
                'content' => 'Content via API',
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('API Post', $data['title']);
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/posts', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => '']) // Missing required fields
        );

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }
}
```

### Response Assertions

```php
class ResponseAssertionsTest extends WebTestCase
{
    public function testResponseAssertions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/page');

        // Status code
        $this->assertResponseIsSuccessful(); // 2xx
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseRedirects(); // 3xx
        $this->assertResponseRedirects('/target');

        // Headers
        $this->assertResponseHasHeader('Content-Type');
        $this->assertResponseHeaderSame('Content-Type', 'text/html; charset=UTF-8');
        $this->assertResponseHeaderNotSame('X-Custom', 'value');

        // Content
        $this->assertSelectorExists('.class');
        $this->assertSelectorNotExists('.missing');
        $this->assertSelectorTextContains('h1', 'Title');
        $this->assertSelectorTextSame('.count', '5');

        // Count selectors
        $this->assertCount(5, $client->getCrawler()->filter('.item'));

        // Form
        $this->assertInputValueSame('username', 'john');
        $this->assertCheckboxChecked('remember_me');
        $this->assertCheckboxNotChecked('newsletter');

        // Page
        $this->assertPageTitleSame('Page Title');
        $this->assertPageTitleContains('Title');

        // Routes
        $this->assertRouteSame('post_show');
        $this->assertRouteSame('post_show', ['id' => 1]);
    }
}
```

---

## 5. Integration Tests

### KernelTestCase Basics

```php
namespace App\Tests\Integration\Service;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderServiceTest extends KernelTestCase
{
    private OrderService $orderService;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $this->orderService = $container->get(OrderService::class);
    }

    public function testCreateOrder(): void
    {
        $order = $this->orderService->createOrder([
            'customer_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->getId());
        $this->assertEquals(1, $order->getCustomerId());
    }
}
```

### Testing with Doctrine

```php
namespace App\Tests\Integration\Repository;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class PostRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PostRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager
            ->getRepository(Post::class);
    }

    public function testFindPublished(): void
    {
        $posts = $this->repository->findPublished();

        $this->assertNotEmpty($posts);
        foreach ($posts as $post) {
            $this->assertTrue($post->isPublished());
        }
    }

    public function testSavePost(): void
    {
        $post = new Post();
        $post->setTitle('Test Post');
        $post->setContent('Content');
        $post->setPublished(true);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $this->assertNotNull($post->getId());

        // Verify it's in database
        $found = $this->repository->find($post->getId());
        $this->assertEquals('Test Post', $found->getTitle());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
    }
}
```

---

## 6. Test Client and Crawler

### Using the Test Client

```php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientExamplesTest extends WebTestCase
{
    public function testClientMethods(): void
    {
        $client = static::createClient();

        // Make request
        $crawler = $client->request('GET', '/posts');

        // Follow links
        $link = $crawler->selectLink('Read more')->link();
        $client->click($link);

        // Submit forms
        $form = $crawler->selectButton('Submit')->form();
        $client->submit($form, [
            'name' => 'value',
        ]);

        // Get response
        $response = $client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        // Follow redirects
        $client->followRedirect();
        $crawler = $client->followRedirects(); // Auto-follow

        // Back/Forward
        $client->back();
        $client->forward();
        $client->reload();

        // Request history
        $client->getHistory();

        // Cookies
        $client->getCookieJar()->get('session_id');

        // Custom headers
        $client->request('GET', '/api', [], [], [
            'HTTP_Authorization' => 'Bearer token',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }
}
```

### Using the Crawler

```php
class CrawlerExamplesTest extends WebTestCase
{
    public function testCrawlerMethods(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/posts');

        // Filter by CSS selector
        $titles = $crawler->filter('.post-title');
        $this->assertCount(10, $titles);

        // Filter by XPath
        $crawler->filterXPath('//div[@class="post"]');

        // Get text
        $text = $crawler->filter('h1')->text();
        $allText = $crawler->filter('.post-title')->each(function ($node) {
            return $node->text();
        });

        // Get HTML
        $html = $crawler->filter('.content')->html();

        // Get attributes
        $href = $crawler->filter('a')->attr('href');
        $id = $crawler->filter('div')->attr('id');

        // Select links
        $link = $crawler->selectLink('Read more')->link();
        $client->click($link);

        // Select forms
        $form = $crawler->selectButton('Submit')->form();

        // Extract data
        $crawler->filter('.post')->each(function ($node) {
            $title = $node->filter('.title')->text();
            $author = $node->filter('.author')->text();
            // Process...
        });

        // First/Last/Eq
        $first = $crawler->filter('.post')->first();
        $last = $crawler->filter('.post')->last();
        $third = $crawler->filter('.post')->eq(2); // 0-indexed

        // Count
        $count = $crawler->filter('.post')->count();
    }
}
```

---

## 7. Testing Forms

### Basic Form Testing

```php
namespace App\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactFormTest extends WebTestCase
{
    public function testContactFormSubmission(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        // Get form
        $form = $crawler->selectButton('Send')->form();

        // Fill form
        $form['contact[name]'] = 'John Doe';
        $form['contact[email]'] = 'john@example.com';
        $form['contact[message]'] = 'Test message';

        // Submit
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();

        $this->assertSelectorTextContains(
            '.alert-success',
            'Message sent successfully'
        );
    }

    public function testFormValidation(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $form = $crawler->selectButton('Send')->form();
        $form['contact[email]'] = 'invalid-email'; // Invalid email

        $client->submit($form);

        $this->assertResponseIsUnprocessable(); // 422
        $this->assertSelectorExists('.invalid-feedback');
    }

    public function testFormWithFile(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/upload');

        $form = $crawler->selectButton('Upload')->form();

        // Upload file
        $form['upload[file]']->upload(__DIR__.'/fixtures/test.pdf');

        $client->submit($form);

        $this->assertResponseRedirects();
    }
}
```

### Testing Form Types Directly

```php
namespace App\Tests\Unit\Form;

use App\Entity\Post;
use App\Form\PostType;
use Symfony\Component\Form\Test\TypeTestCase;

class PostTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'title' => 'Test Post',
            'content' => 'Test content',
            'published' => true,
        ];

        $model = new Post();
        $form = $this->factory->create(PostType::class, $model);

        $expected = new Post();
        $expected->setTitle('Test Post');
        $expected->setContent('Test content');
        $expected->setPublished(true);

        // Submit form
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);

        // Check form children
        $this->assertTrue($form->has('title'));
        $this->assertTrue($form->has('content'));

        // Check view data
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
```

---

## 8. Database Testing

### Using Fixtures

```php
namespace App\Tests\Functional;

use App\DataFixtures\PostFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostWithFixturesTest extends WebTestCase
{
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Load fixtures
        $loader = new Loader();
        $loader->addFixture(new PostFixtures());

        $purger = new ORMPurger($entityManager);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testPostsList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/posts');

        $this->assertResponseIsSuccessful();
        // Fixtures loaded, we know there are posts
        $this->assertSelectorExists('.post-item');
    }
}
```

### Database Transactions

```php
namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionalTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->connection = $kernel->getContainer()
            ->get('doctrine.dbal.default_connection');

        // Start transaction
        $this->connection->beginTransaction();
    }

    public function testDatabaseOperation(): void
    {
        // Database changes here...
        // Will be rolled back in tearDown
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        $this->connection->rollBack();

        parent::tearDown();
    }
}
```

### In-Memory SQLite Database

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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\Tools\SchemaTool;

class SqliteTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Create schema
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public function testWithFreshDatabase(): void
    {
        // Test with clean in-memory database
    }
}
```

---

## 9. Mocking and Test Doubles

### Creating Mocks

```php
namespace App\Tests\Unit\Service;

use App\Repository\UserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testGetActiveUsers(): void
    {
        // Create mock
        $repository = $this->createMock(UserRepository::class);

        // Configure mock
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['active' => true])
            ->willReturn([/* users */]);

        $service = new UserService($repository);
        $users = $service->getActiveUsers();

        $this->assertIsArray($users);
    }

    public function testMultipleCalls(): void
    {
        $repository = $this->createMock(UserRepository::class);

        // Expect multiple calls
        $repository->expects($this->exactly(2))
            ->method('find')
            ->willReturn(/* user */);

        $service = new UserService($repository);
        $service->getUser(1);
        $service->getUser(2);
    }

    public function testDifferentReturnValues(): void
    {
        $repository = $this->createMock(UserRepository::class);

        // Return different values on consecutive calls
        $repository->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($user1, $user2);

        // Or use callback
        $repository->method('find')
            ->willReturnCallback(function ($id) {
                return $id === 1 ? $user1 : $user2;
            });
    }

    public function testExceptionThrown(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->method('find')
            ->willThrowException(new \RuntimeException('Not found'));

        $service = new UserService($repository);

        $this->expectException(\RuntimeException::class);
        $service->getUser(999);
    }
}
```

### Stubs vs Mocks

```php
class StubsAndMocksTest extends TestCase
{
    public function testWithStub(): void
    {
        // Stub - just return values, no expectations
        $stub = $this->createStub(MailerInterface::class);
        $stub->method('send')->willReturn(true);

        $service = new NotificationService($stub);
        $result = $service->notify($user);

        $this->assertTrue($result);
    }

    public function testWithMock(): void
    {
        // Mock - verify method calls
        $mock = $this->createMock(MailerInterface::class);
        $mock->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                return $email->getTo() === 'user@example.com';
            }))
            ->willReturn(true);

        $service = new NotificationService($mock);
        $service->notify($user);
    }
}
```

### Test Doubles in Symfony

```php
namespace App\Tests\Functional;

use App\Service\ExternalApiClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ControllerWithMockTest extends WebTestCase
{
    public function testWithMockedService(): void
    {
        $client = static::createClient();

        // Create mock
        $apiClient = $this->createMock(ExternalApiClient::class);
        $apiClient->method('fetchData')->willReturn(['data' => 'test']);

        // Replace service in container
        $client->getContainer()->set(ExternalApiClient::class, $apiClient);

        $client->request('GET', '/api/data');

        $this->assertResponseIsSuccessful();
    }
}
```

---

## 10. PHPUnit Bridge

### Deprecation Handling

```php
// .env.test
SYMFONY_DEPRECATIONS_HELPER=weak
# Options: weak, disabled, max[self]=X, max[direct]=X, max[indirect]=X
```

```xml
<!-- phpunit.xml.dist -->
<php>
    <env name="SYMFONY_DEPRECATIONS_HELPER" value="weak" />
</php>
```

### Triggering Deprecations

```php
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
        $this->expectDeprecation('Using method X is deprecated');

        // Call deprecated code
        $result = $this->legacyMethod();
    }
}
```

### ClockMock

```php
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use PHPUnit\Framework\TestCase;

class TimeBasedTest extends TestCase
{
    use ClockSensitiveTrait;

    public function testTimeBasedLogic(): void
    {
        // Mock time
        self::mockTime('2024-01-01 12:00:00');

        $service = new TimeService();
        $result = $service->getCurrentTime();

        $this->assertEquals('2024-01-01 12:00:00', $result);
    }
}
```

---

## 11. Code Coverage

### Generating Coverage

```bash
# HTML coverage report
php bin/phpunit --coverage-html var/coverage

# Text coverage summary
php bin/phpunit --coverage-text

# Clover XML (for CI)
php bin/phpunit --coverage-clover coverage.xml

# Require minimum coverage
php bin/phpunit --coverage-text --coverage-filter=src --min=80
```

### Coverage Configuration

```xml
<!-- phpunit.xml.dist -->
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <exclude>
        <directory>src/Entity</directory>
        <directory>src/DataFixtures</directory>
        <file>src/Kernel.php</file>
    </exclude>
    <report>
        <html outputDirectory="var/coverage"/>
        <text outputFile="php://stdout" showOnlySummary="true"/>
    </report>
</coverage>
```

### Coverage Annotations

```php
/**
 * @codeCoverageIgnore
 */
class IgnoredClass
{
    // Not included in coverage
}

class PartialCoverage
{
    public function covered(): void
    {
        // Covered
    }

    /**
     * @codeCoverageIgnore
     */
    public function notCovered(): void
    {
        // Ignored in coverage
    }
}
```

---

## 12. Data Providers

### Basic Data Provider

```php
class DataProviderTest extends TestCase
{
    /**
     * @dataProvider priceProvider
     */
    public function testCalculateWithTax(float $price, float $tax, float $expected): void
    {
        $calculator = new PriceCalculator();
        $result = $calculator->calculateWithTax($price, $tax);

        $this->assertEquals($expected, $result);
    }

    public static function priceProvider(): array
    {
        return [
            'basic' => [100, 0.20, 120],
            'no tax' => [100, 0, 100],
            'high tax' => [100, 0.50, 150],
            'small price' => [10, 0.20, 12],
        ];
    }
}
```

### Named Data Sets

```php
class NamedDataProviderTest extends TestCase
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
        yield 'valid email' => ['user@example.com', true];
        yield 'invalid format' => ['invalid-email', false];
        yield 'missing domain' => ['user@', false];
        yield 'missing at sign' => ['userexample.com', false];
    }
}
```

### Multiple Data Providers

```php
class MultipleProvidersTest extends TestCase
{
    /**
     * @dataProvider validEmailProvider
     * @dataProvider invalidEmailProvider
     */
    public function testEmail(string $email, bool $expected): void
    {
        $result = $this->validator->validate($email);
        $this->assertEquals($expected, $result);
    }

    public static function validEmailProvider(): array
    {
        return [
            ['user@example.com', true],
            ['test@domain.co.uk', true],
        ];
    }

    public static function invalidEmailProvider(): array
    {
        return [
            ['invalid', false],
            ['@example.com', false],
        ];
    }
}
```

---

## Best Practices

### 1. Test Independence

```php
// GOOD - Each test is independent
class GoodTest extends TestCase
{
    public function testFirst(): void
    {
        $service = new Service();
        // Test...
    }

    public function testSecond(): void
    {
        $service = new Service(); // Fresh instance
        // Test...
    }
}

// BAD - Tests depend on each other
class BadTest extends TestCase
{
    private $service;

    public function testFirst(): void
    {
        $this->service = new Service();
        $this->service->setValue(10);
    }

    public function testSecond(): void
    {
        // Depends on testFirst running first
        $this->assertEquals(10, $this->service->getValue());
    }
}
```

### 2. Arrange-Act-Assert (AAA)

```php
public function testCreateOrder(): void
{
    // Arrange - Setup
    $customer = new Customer('John Doe');
    $product = new Product('Widget', 10.00);
    $service = new OrderService();

    // Act - Execute
    $order = $service->createOrder($customer, [$product]);

    // Assert - Verify
    $this->assertInstanceOf(Order::class, $order);
    $this->assertEquals($customer, $order->getCustomer());
    $this->assertCount(1, $order->getItems());
}
```

### 3. Descriptive Test Names

```php
// GOOD - Clear, descriptive names
public function testCalculateDiscountReturnsTenPercentOffForPremiumCustomers(): void
public function testLoginFailsWithInvalidCredentials(): void
public function testEmailValidatorRejectsEmailsWithoutAtSign(): void

// BAD - Unclear names
public function testCalculate(): void
public function testLogin(): void
public function testEmail(): void
```

---

## Resources

- [Symfony Testing Documentation](https://symfony.com/doc/current/testing.html)
- [PHPUnit Documentation](https://docs.phpunit.de/)
- [PHPUnit Bridge](https://symfony.com/doc/current/components/phpunit_bridge.html)
- [Doctrine Testing](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/testing.html)
