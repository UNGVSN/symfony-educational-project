# Advanced Testing Topics - Deep Dive

Advanced concepts and techniques for mastering testing in Symfony applications.

---

## Table of Contents

1. [Mocking Services and Dependencies](#mocking-services-and-dependencies)
2. [Test Doubles: Mocks, Stubs, Fakes, and Spies](#test-doubles-mocks-stubs-fakes-and-spies)
3. [PHPUnit Data Providers](#phpunit-data-providers)
4. [Testing with Databases](#testing-with-databases)
5. [PHPUnit Bridge and Deprecation Handling](#phpunit-bridge-and-deprecation-handling)
6. [Code Coverage Analysis](#code-coverage-analysis)
7. [Testing Async Code](#testing-async-code)
8. [Performance Testing](#performance-testing)
9. [Advanced Assertions](#advanced-assertions)
10. [Testing Best Practices](#testing-best-practices)

---

## Mocking Services and Dependencies

### Understanding Mocks

Mocks are test doubles that allow you to isolate the code under test from its dependencies.

```php
namespace App\Tests\Unit\Service;

use App\Repository\UserRepository;
use App\Service\EmailService;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private EmailService $emailService;
    private LoggerInterface $logger;
    private UserService $userService;

    protected function setUp(): void
    {
        // Create mocks for all dependencies
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->emailService = $this->createMock(EmailService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->emailService,
            $this->logger
        );
    }

    public function testRegisterUser(): void
    {
        // Arrange: Configure mock expectations
        $userData = [
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123',
        ];

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newuser@example.com'])
            ->willReturn(null); // User doesn't exist

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($user) {
                return $user->getEmail() === 'newuser@example.com';
            }));

        $this->emailService->expects($this->once())
            ->method('sendWelcomeEmail')
            ->with($this->isInstanceOf(User::class));

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'User registered successfully',
                $this->arrayHasKey('email')
            );

        // Act
        $user = $this->userService->register($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('newuser@example.com', $user->getEmail());
    }
}
```

### Advanced Mock Configurations

```php
class AdvancedMockingTest extends TestCase
{
    public function testMultipleMethodCalls(): void
    {
        $repository = $this->createMock(ProductRepository::class);

        // Expect exactly 3 calls
        $repository->expects($this->exactly(3))
            ->method('find')
            ->willReturnOnConsecutiveCalls(
                new Product('Product 1'),
                new Product('Product 2'),
                new Product('Product 3')
            );

        $service = new ProductService($repository);

        $product1 = $service->getProduct(1);
        $product2 = $service->getProduct(2);
        $product3 = $service->getProduct(3);

        $this->assertEquals('Product 1', $product1->getName());
        $this->assertEquals('Product 2', $product2->getName());
        $this->assertEquals('Product 3', $product3->getName());
    }

    public function testConditionalBehavior(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $cache->method('get')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'user:1' => ['id' => 1, 'name' => 'John'],
                    'user:2' => ['id' => 2, 'name' => 'Jane'],
                    default => null,
                };
            });

        $service = new CacheService($cache);

        $user1 = $service->getUser(1);
        $user2 = $service->getUser(2);
        $user3 = $service->getUser(3);

        $this->assertEquals('John', $user1['name']);
        $this->assertEquals('Jane', $user2['name']);
        $this->assertNull($user3);
    }

    public function testMethodChaining(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        // Configure for method chaining
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();

        $query = $this->createMock(Query::class);
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->method('getResult')->willReturn([new Product()]);

        // Use the fluent interface
        $result = $queryBuilder
            ->select('p')
            ->from('Product', 'p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($result);
    }

    public function testExceptionHandling(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->method('find')
            ->willThrowException(new \RuntimeException('Database connection failed'));

        $service = new UserService($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection failed');

        $service->getUser(1);
    }

    public function testArgumentMatchers(): void
    {
        $mailer = $this->createMock(MailerInterface::class);

        $mailer->expects($this->once())
            ->method('send')
            ->with(
                $this->callback(function ($email) {
                    $to = $email->getTo()[0];
                    return $to->getAddress() === 'user@example.com'
                        && $email->getSubject() === 'Welcome!'
                        && str_contains($email->getHtmlBody(), 'verification link');
                })
            );

        $service = new NotificationService($mailer);
        $service->sendWelcomeEmail(new User('user@example.com'));
    }
}
```

### Partial Mocks

```php
class PartialMockTest extends TestCase
{
    public function testPartialMock(): void
    {
        // Mock only specific methods, keep others real
        $service = $this->getMockBuilder(PaymentService::class)
            ->onlyMethods(['callExternalApi'])
            ->getMock();

        $service->method('callExternalApi')
            ->willReturn(['status' => 'success', 'transaction_id' => 'txn_123']);

        // callExternalApi is mocked, but processPayment uses real implementation
        $result = $service->processPayment(100.00, 'USD');

        $this->assertTrue($result->isSuccessful());
        $this->assertEquals('txn_123', $result->getTransactionId());
    }

    public function testAbstractClassMock(): void
    {
        // Mock abstract class
        $abstract = $this->getMockBuilder(AbstractRepository::class)
            ->getMockForAbstractClass();

        $abstract->method('findAll')
            ->willReturn([new Entity()]);

        $this->assertNotEmpty($abstract->findAll());
    }
}
```

---

## Test Doubles: Mocks, Stubs, Fakes, and Spies

### Dummy Objects

Objects passed around but never actually used.

```php
class DummyExampleTest extends TestCase
{
    public function testWithDummy(): void
    {
        // Logger is required but not used in this test
        $dummyLogger = $this->createStub(LoggerInterface::class);

        $calculator = new Calculator($dummyLogger);
        $result = $calculator->add(5, 3);

        $this->assertEquals(8, $result);
    }
}
```

### Stubs

Provide canned answers to calls made during the test.

```php
class StubExampleTest extends TestCase
{
    public function testWithStub(): void
    {
        $configStub = $this->createStub(ConfigRepository::class);

        // Configure stub to return specific values
        $configStub->method('get')
            ->willReturnMap([
                ['app.name', 'My Application'],
                ['app.version', '1.0.0'],
                ['app.debug', false],
            ]);

        $service = new ApplicationService($configStub);

        $this->assertEquals('My Application', $service->getAppName());
        $this->assertEquals('1.0.0', $service->getVersion());
        $this->assertFalse($service->isDebugMode());
    }
}
```

### Mocks

Pre-programmed with expectations which form a specification of the calls they are expected to receive.

```php
class MockExampleTest extends TestCase
{
    public function testWithMock(): void
    {
        $emailServiceMock = $this->createMock(EmailService::class);

        // Set expectations - MUST be called
        $emailServiceMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('user@example.com'),
                $this->stringContains('Order Confirmation')
            )
            ->willReturn(true);

        $orderService = new OrderService($emailServiceMock);
        $orderService->completeOrder(new Order());

        // Mock automatically verifies expectations in tearDown
    }
}
```

### Spies

Record information about how they were called.

```php
class SpyExampleTest extends TestCase
{
    public function testWithSpy(): void
    {
        $loggerSpy = $this->createMock(LoggerInterface::class);

        $calls = [];
        $loggerSpy->method('info')
            ->willReturnCallback(function ($message, $context = []) use (&$calls) {
                $calls[] = ['message' => $message, 'context' => $context];
            });

        $service = new UserService($loggerSpy);
        $service->createUser(['email' => 'user@example.com']);

        // Verify the spy recorded the correct calls
        $this->assertCount(1, $calls);
        $this->assertEquals('User created', $calls[0]['message']);
        $this->assertArrayHasKey('email', $calls[0]['context']);
    }
}
```

### Fakes

Working implementations, but usually take shortcuts (e.g., in-memory database).

```php
class InMemoryUserRepository implements UserRepositoryInterface
{
    private array $users = [];
    private int $nextId = 1;

    public function save(User $user): void
    {
        if ($user->getId() === null) {
            $user->setId($this->nextId++);
        }
        $this->users[$user->getId()] = $user;
    }

    public function find(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->users);
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        return null;
    }

    public function delete(User $user): void
    {
        unset($this->users[$user->getId()]);
    }
}

class FakeRepositoryTest extends TestCase
{
    public function testWithFake(): void
    {
        $repository = new InMemoryUserRepository();

        $user = new User('test@example.com');
        $repository->save($user);

        $this->assertNotNull($user->getId());

        $found = $repository->find($user->getId());
        $this->assertSame($user, $found);

        $byEmail = $repository->findByEmail('test@example.com');
        $this->assertSame($user, $byEmail);
    }
}
```

---

## PHPUnit Data Providers

### Basic Data Providers

```php
class DataProviderTest extends TestCase
{
    /**
     * @dataProvider calculationProvider
     */
    public function testCalculation(float $a, float $b, string $operation, float $expected): void
    {
        $calculator = new Calculator();
        $result = $calculator->calculate($a, $b, $operation);

        $this->assertEquals($expected, $result);
    }

    public static function calculationProvider(): array
    {
        return [
            'addition' => [10, 5, '+', 15],
            'subtraction' => [10, 5, '-', 5],
            'multiplication' => [10, 5, '*', 50],
            'division' => [10, 5, '/', 2],
            'zero division' => [10, 0, '/', INF],
        ];
    }
}
```

### Generators for Large Datasets

```php
class GeneratorProviderTest extends TestCase
{
    /**
     * @dataProvider largeDatasetProvider
     */
    public function testWithLargeDataset(int $value): void
    {
        $this->assertGreaterThan(0, $value);
    }

    public static function largeDatasetProvider(): \Generator
    {
        for ($i = 1; $i <= 1000; $i++) {
            yield "value {$i}" => [$i];
        }
    }

    /**
     * @dataProvider emailProvider
     */
    public function testEmailValidation(string $email, bool $expected): void
    {
        $validator = new EmailValidator();
        $this->assertEquals($expected, $validator->isValid($email));
    }

    public static function emailProvider(): iterable
    {
        yield 'valid simple' => ['user@example.com', true];
        yield 'valid with subdomain' => ['user@mail.example.com', true];
        yield 'valid with plus' => ['user+tag@example.com', true];
        yield 'valid with dash' => ['user-name@example.com', true];
        yield 'invalid no at' => ['userexample.com', false];
        yield 'invalid no domain' => ['user@', false];
        yield 'invalid no user' => ['@example.com', false];
        yield 'invalid spaces' => ['user @example.com', false];
    }
}
```

### External Data Sources

```php
class ExternalDataProviderTest extends TestCase
{
    /**
     * @dataProvider csvDataProvider
     */
    public function testWithCsvData(string $input, string $expected): void
    {
        $processor = new DataProcessor();
        $result = $processor->process($input);

        $this->assertEquals($expected, $result);
    }

    public static function csvDataProvider(): array
    {
        $data = [];
        $file = fopen(__DIR__ . '/fixtures/test-data.csv', 'r');

        // Skip header row
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data[$row[0]] = [$row[1], $row[2]];
        }

        fclose($file);

        return $data;
    }

    /**
     * @dataProvider jsonDataProvider
     */
    public function testWithJsonData(array $input, array $expected): void
    {
        $service = new DataService();
        $result = $service->transform($input);

        $this->assertEquals($expected, $result);
    }

    public static function jsonDataProvider(): array
    {
        $json = file_get_contents(__DIR__ . '/fixtures/test-data.json');
        $testCases = json_decode($json, true);

        $data = [];
        foreach ($testCases as $name => $case) {
            $data[$name] = [$case['input'], $case['expected']];
        }

        return $data;
    }
}
```

### Combining Multiple Providers

```php
class CombinedProvidersTest extends TestCase
{
    /**
     * @dataProvider validInputProvider
     * @dataProvider invalidInputProvider
     */
    public function testInputValidation(string $input, bool $isValid): void
    {
        $validator = new InputValidator();
        $result = $validator->validate($input);

        $this->assertEquals($isValid, $result);
    }

    public static function validInputProvider(): array
    {
        return [
            'alphanumeric' => ['abc123', true],
            'with dashes' => ['user-name', true],
            'with underscores' => ['user_name', true],
        ];
    }

    public static function invalidInputProvider(): array
    {
        return [
            'special chars' => ['user@name', false],
            'spaces' => ['user name', false],
            'empty' => ['', false],
        ];
    }
}
```

---

## Testing with Databases

### Strategy 1: Fixtures with DoctrineFixturesBundle

```php
namespace App\Tests\Functional;

use App\DataFixtures\UserFixtures;
use App\DataFixtures\ProductFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->loadFixtures([
            new UserFixtures(),
            new ProductFixtures(),
        ]);
    }

    private function loadFixtures(array $fixtures): void
    {
        $loader = new Loader();
        foreach ($fixtures as $fixture) {
            $loader->addFixture($fixture);
        }

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function testProductList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/products');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.product-item');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
```

### Strategy 2: Database Transactions

```php
namespace App\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TransactionalTestCase extends KernelTestCase
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

        // Start transaction before each test
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback all database changes
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $this->entityManager->close();
        parent::tearDown();
    }
}

class UserServiceIntegrationTest extends TransactionalTestCase
{
    public function testCreateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->assertNotNull($user->getId());

        // Verify in database
        $found = $this->entityManager
            ->getRepository(User::class)
            ->find($user->getId());

        $this->assertEquals('test@example.com', $found->getEmail());

        // Changes will be rolled back automatically in tearDown
    }
}
```

### Strategy 3: In-Memory SQLite

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

abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->createDatabaseSchema();
    }

    private function createDatabaseSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager
            ->getMetadataFactory()
            ->getAllMetadata();

        // Drop and recreate schema
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}

class ProductRepositoryTest extends DatabaseTestCase
{
    public function testFindActiveProducts(): void
    {
        // Create test data
        $product1 = new Product();
        $product1->setName('Active Product');
        $product1->setActive(true);

        $product2 = new Product();
        $product2->setName('Inactive Product');
        $product2->setActive(false);

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->flush();

        // Test
        $repository = $this->entityManager->getRepository(Product::class);
        $activeProducts = $repository->findBy(['active' => true]);

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('Active Product', $activeProducts[0]->getName());
    }
}
```

### Strategy 4: DAMADoctrineTestBundle

```bash
composer require --dev dama/doctrine-test-bundle
```

```yaml
# config/bundles.php
return [
    // ...
    DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class => ['test' => true],
];
```

```php
namespace App\Tests\Integration;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AutoRollbackTest extends KernelTestCase
{
    // Automatically wraps each test in a database transaction
    // No need to manually handle transactions

    public function testUserCreation(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $user = new User('test@example.com');
        $entityManager->persist($user);
        $entityManager->flush();

        $this->assertNotNull($user->getId());

        // Automatically rolled back after test
    }

    public function testAnotherTest(): void
    {
        // Database is clean - previous test was rolled back
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $users = $entityManager->getRepository(User::class)->findAll();

        // Only fixture data, no data from previous test
        $this->assertCount(0, $users);
    }
}
```

---

## PHPUnit Bridge and Deprecation Handling

### Configuration

```xml
<!-- phpunit.xml.dist -->
<php>
    <!-- Deprecation handling modes:
         - "disabled": Don't track deprecations
         - "weak": Show summary but don't fail
         - "weak_vendors": Weak mode for vendor deprecations
         - 0: Fail on any deprecation
         - max[self]=X: Maximum self deprecations allowed
         - max[direct]=X: Maximum direct deprecations allowed
         - max[indirect]=X: Maximum indirect deprecations allowed
    -->
    <env name="SYMFONY_DEPRECATIONS_HELPER" value="max[self]=0&amp;max[direct]=0" />
</php>
```

### Deprecation Types

```php
namespace App\Tests\Unit;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;

class DeprecationTypesTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * Self deprecation: Triggered by your own code
     * @group legacy
     */
    public function testSelfDeprecation(): void
    {
        $this->expectDeprecation('OldService::process() is deprecated since 2.0, use NewService::execute() instead.');

        $service = new OldService();
        $service->process(); // Your deprecated code
    }

    /**
     * Direct deprecation: Triggered by vendor code you call directly
     */
    public function testDirectDeprecation(): void
    {
        // When you call deprecated vendor code directly
        // Tracked as "direct" deprecation
    }

    /**
     * Indirect deprecation: Triggered by vendor calling other deprecated vendor code
     */
    public function testIndirectDeprecation(): void
    {
        // When vendor A calls deprecated code in vendor B
        // Tracked as "indirect" deprecation
    }
}
```

### Triggering Deprecations

```php
namespace App\Service;

class LegacyService
{
    /**
     * @deprecated since 2.0, use newMethod() instead
     */
    public function oldMethod(): void
    {
        trigger_deprecation(
            'app/package',
            '2.0',
            'The %s() method is deprecated, use %s() instead.',
            __METHOD__,
            'newMethod'
        );

        // Old implementation
        $this->doSomething();
    }

    public function newMethod(): void
    {
        // New implementation
        $this->doSomethingBetter();
    }
}
```

### Testing Deprecated Code

```php
namespace App\Tests\Unit;

use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use PHPUnit\Framework\TestCase;

class LegacyCodeTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testOldMethod(): void
    {
        $this->expectDeprecation('The App\Service\LegacyService::oldMethod() method is deprecated, use newMethod() instead.');

        $service = new LegacyService();
        $result = $service->oldMethod();

        $this->assertNotNull($result);
    }

    public function testNewMethod(): void
    {
        // No deprecation expected
        $service = new LegacyService();
        $result = $service->newMethod();

        $this->assertNotNull($result);
    }

    /**
     * @group legacy
     */
    public function testMultipleDeprecations(): void
    {
        $this->expectDeprecation('First deprecation message');
        $this->expectDeprecation('Second deprecation message');

        $service = new LegacyService();
        $service->triggerMultipleDeprecations();
    }
}
```

---

## Code Coverage Analysis

### Generating Coverage Reports

```bash
# Generate HTML coverage report
php bin/phpunit --coverage-html var/coverage

# Generate text coverage in console
php bin/phpunit --coverage-text

# Generate Clover XML (for CI)
php bin/phpunit --coverage-clover var/coverage/clover.xml

# Generate multiple formats
php bin/phpunit \
    --coverage-html var/coverage \
    --coverage-clover var/coverage/clover.xml \
    --coverage-text
```

### Coverage Configuration

```xml
<!-- phpunit.xml.dist -->
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
        <directory>src/DependencyInjection</directory>
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
 * Exclude entire class from coverage
 * @codeCoverageIgnore
 */
class DebugHelper
{
    public function debug(): void
    {
        // Not included in coverage
    }
}

class PaymentProcessor
{
    public function process(Payment $payment): Result
    {
        $this->validate($payment);

        // @codeCoverageIgnoreStart
        if (defined('DEBUG_PAYMENTS') && DEBUG_PAYMENTS) {
            $this->logDetailedDebugInfo($payment);
        }
        // @codeCoverageIgnoreEnd

        return $this->executePayment($payment);
    }

    /**
     * @codeCoverageIgnore
     */
    private function logDetailedDebugInfo(Payment $payment): void
    {
        // Debug code not included in coverage
        var_dump($payment);
    }
}
```

### Analyzing Coverage

```
Code Coverage Report:
  2025-01-15 10:30:00

 Summary:
  Classes:  87.50% (21/24)
  Methods:  82.14% (46/56)
  Lines:    79.33% (357/450)

 App\Service\UserService
  Methods: 100.00% ( 6/ 6)
  Lines:    95.45% (21/22)

 App\Service\PaymentService
  Methods:  83.33% ( 5/ 6)
  Lines:    76.92% (30/39)

 App\Controller\PostController
  Methods:  75.00% ( 6/ 8)
  Lines:    68.00% (34/50)
```

**Coverage Guidelines:**
- 80%+ coverage: Good
- 60-80% coverage: Acceptable
- Below 60%: Needs improvement

**Focus coverage on:**
- Critical business logic
- Complex algorithms
- Error handling paths
- Edge cases

**Don't obsess over:**
- Simple getters/setters
- Framework code
- Generated code
- Trivial methods

---

## Testing Async Code

### Testing with Symfony Messenger

```php
namespace App\Tests\Integration\Message;

use App\Message\SendEmailMessage;
use App\MessageHandler\SendEmailMessageHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Symfony\Component\Messenger\MessageBusInterface;

class MessengerTest extends KernelTestCase
{
    public function testMessageIsDispatched(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $bus = $container->get(MessageBusInterface::class);
        $transport = $container->get('messenger.transport.async');

        $this->assertInstanceOf(InMemoryTransport::class, $transport);

        // Dispatch message
        $message = new SendEmailMessage('user@example.com', 'Subject', 'Body');
        $bus->dispatch($message);

        // Assert message was sent to transport
        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);
        $this->assertInstanceOf(SendEmailMessage::class, $envelopes[0]->getMessage());
    }

    public function testMessageHandler(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send');

        $handler = new SendEmailMessageHandler($mailer);

        $message = new SendEmailMessage('user@example.com', 'Subject', 'Body');
        $handler($message);
    }
}
```

### Testing Async Operations with Promises

```php
namespace App\Tests\Unit\Service;

use App\Service\AsyncService;
use PHPUnit\Framework\TestCase;
use React\Promise\Promise;
use React\Promise\Deferred;

class AsyncServiceTest extends TestCase
{
    public function testAsyncOperation(): void
    {
        $service = new AsyncService();

        $result = null;
        $service->fetchDataAsync('resource')
            ->then(function ($data) use (&$result) {
                $result = $data;
            });

        // Wait for promise to resolve
        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result);
    }

    public function testAsyncError(): void
    {
        $service = new AsyncService();

        $error = null;
        $service->fetchDataAsync('invalid')
            ->otherwise(function ($e) use (&$error) {
                $error = $e;
            });

        $this->assertInstanceOf(\Exception::class, $error);
    }
}
```

---

## Performance Testing

### Measuring Execution Time

```php
namespace App\Tests\Performance;

use PHPUnit\Framework\TestCase;

class PerformanceTest extends TestCase
{
    public function testSearchPerformance(): void
    {
        $service = new SearchService();

        $startTime = microtime(true);

        $results = $service->search('query', 1000);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert completed within 1 second
        $this->assertLessThan(1.0, $executionTime,
            "Search took {$executionTime}s, expected < 1s"
        );

        $this->assertNotEmpty($results);
    }

    public function testMemoryUsage(): void
    {
        $service = new DataProcessor();

        $memoryBefore = memory_get_usage();

        $service->processLargeDataset(10000);

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Assert memory usage is under 10MB
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed,
            "Memory usage: " . ($memoryUsed / 1024 / 1024) . "MB"
        );
    }

    public function testDatabaseQueryPerformance(): void
    {
        $kernel = self::bootKernel();
        $repository = $kernel->getContainer()
            ->get(ProductRepository::class);

        $startTime = microtime(true);

        $repository->findComplexQuery(['active' => true], 100);

        $executionTime = microtime(true) - $startTime;

        // Query should complete in under 100ms
        $this->assertLessThan(0.1, $executionTime);
    }
}
```

### Profiling with Blackfire (Integration Example)

```php
namespace App\Tests\Performance;

use Blackfire\Bridge\PhpUnit\TestCaseTrait;
use Blackfire\Profile\Configuration as ProfileConfiguration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlackfireTest extends WebTestCase
{
    use TestCaseTrait;

    public function testHomepagePerformance(): void
    {
        $config = new ProfileConfiguration();
        $config->assert('main.wall_time < 100ms', 'Homepage wall time');
        $config->assert('main.memory < 5mb', 'Homepage memory');

        $this->assertBlackfire($config, function () {
            $client = static::createClient();
            $client->request('GET', '/');
        });
    }
}
```

---

## Advanced Assertions

### Custom Assertions

```php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\Constraint;

class CustomAssertionsTest extends TestCase
{
    public function testUserWithCustomAssertion(): void
    {
        $user = new User('john@example.com');

        $this->assertUserHasValidEmail($user);
        $this->assertUserIsActive($user);
    }

    private function assertUserHasValidEmail(User $user): void
    {
        $this->assertNotNull($user->getEmail(), 'User must have an email');
        $this->assertMatchesRegularExpression(
            '/^[^@]+@[^@]+\.[^@]+$/',
            $user->getEmail(),
            'Email must be valid'
        );
    }

    private function assertUserIsActive(User $user): void
    {
        $this->assertTrue($user->isActive(), 'User must be active');
        $this->assertNotNull($user->getActivatedAt(), 'User must have activation date');
    }
}

// Custom Constraint
class IsValidJson extends Constraint
{
    public function matches($other): bool
    {
        json_decode($other);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function toString(): string
    {
        return 'is valid JSON';
    }

    protected function failureDescription($other): string
    {
        return 'string ' . $this->toString();
    }
}

class JsonTest extends TestCase
{
    public function testValidJson(): void
    {
        $json = '{"name": "John", "age": 30}';

        $this->assertThat($json, new IsValidJson());
    }
}
```

---

## Testing Best Practices

### 1. F.I.R.S.T Principles

- **Fast**: Tests should run quickly
- **Independent**: Tests should not depend on each other
- **Repeatable**: Same results every time
- **Self-Validating**: Clear pass/fail
- **Timely**: Written alongside production code

### 2. AAA Pattern (Arrange-Act-Assert)

```php
public function testCreateOrder(): void
{
    // Arrange: Set up test data
    $customer = new Customer('John Doe');
    $product = new Product('Widget', 10.00);
    $service = new OrderService($this->repository);

    // Act: Execute the operation
    $order = $service->createOrder($customer, [$product]);

    // Assert: Verify the results
    $this->assertInstanceOf(Order::class, $order);
    $this->assertEquals(10.00, $order->getTotal());
}
```

### 3. Test Naming

```php
// Pattern: test{MethodName}_{Scenario}_{ExpectedBehavior}

public function testCreateOrder_WithValidData_CreatesOrder(): void
public function testCreateOrder_WithInvalidData_ThrowsException(): void
public function testCalculateTotal_WithTax_IncludesTaxInTotal(): void
```

### 4. One Logical Assert Per Test

```php
// GOOD: Focused tests
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
public function testUserConstruction(): void
{
    $user = new User('john@example.com');

    $this->assertEquals('john@example.com', $user->getEmail());
    $this->assertTrue($user->isActive());
    $this->assertNull($user->getName());
}
```

### 5. Test Organization

```
tests/
├── Unit/                    # Pure unit tests
│   ├── Entity/
│   ├── Service/
│   └── Util/
├── Integration/             # Integration tests
│   ├── Repository/
│   └── Service/
├── Functional/              # Functional tests
│   ├── Controller/
│   └── Command/
├── E2E/                     # End-to-end tests
└── fixtures/                # Test data
    ├── files/
    └── data/
```

### 6. Avoid Testing Implementation Details

```php
// BAD: Testing implementation
public function testUserRepository_CallsEntityManager(): void
{
    $em = $this->createMock(EntityManager::class);
    $em->expects($this->once())->method('persist');
    $em->expects($this->once())->method('flush');

    $repository = new UserRepository($em);
    $repository->save(new User());
}

// GOOD: Testing behavior
public function testUserRepository_SavesPersistsUser(): void
{
    $user = new User('test@example.com');
    $this->repository->save($user);

    $found = $this->repository->find($user->getId());
    $this->assertNotNull($found);
    $this->assertEquals('test@example.com', $found->getEmail());
}
```

### 7. Keep Tests Simple and Readable

```php
// BAD: Complex test
public function testComplexScenario(): void
{
    $data = [/* ... complex setup ... */];
    $result = $this->service->process($data);
    $this->assertEquals(
        array_map(function($x) { return $x['value'] * 2; },
        array_filter($data, function($x) { return $x['active']; })),
        $result
    );
}

// GOOD: Clear and simple
public function testProcess_DoublesActiveValues(): void
{
    $data = [
        ['value' => 5, 'active' => true],
        ['value' => 10, 'active' => false],
    ];

    $result = $this->service->process($data);

    $this->assertEquals([10], $result); // Only active values, doubled
}
```

---

This deep dive provides advanced techniques for comprehensive testing in Symfony applications. Combine these patterns with the core concepts to build a robust, maintainable test suite.
