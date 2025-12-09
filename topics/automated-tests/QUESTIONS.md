# Testing Practice Questions

Test your knowledge of automated testing in Symfony with these practice questions.

---

## Questions

### Question 1: Testing Pyramid

**Question**: Explain the testing pyramid concept and provide the recommended percentage distribution for each test type in a typical Symfony application.

---

### Question 2: PHPUnit Configuration

**Question**: What is the purpose of the `SYMFONY_DEPRECATIONS_HELPER` environment variable, and what are the different modes you can set it to?

---

### Question 3: Unit Test vs Integration Test

**Question**: What is the difference between a unit test and an integration test? Provide a code example of each.

---

### Question 4: Mock Expectations

**Question**: Given the following code, write a unit test that properly mocks the `UserRepository` dependency:

```php
namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;

class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function activateUser(int $userId): bool
    {
        $user = $this->repository->find($userId);

        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $user->setActive(true);
        $this->repository->save($user);

        return true;
    }
}
```

---

### Question 5: WebTestCase Assertions

**Question**: Write a functional test for a blog post page that verifies:
- The response is successful (200)
- The page title contains "My Blog Post"
- There is exactly one h1 element
- The post content div exists
- The author name is "John Doe"

---

### Question 6: Test Client Methods

**Question**: What is the difference between `$client->request()`, `$client->clickLink()`, and `$client->submit()`? When would you use each?

---

### Question 7: Data Providers

**Question**: Create a data provider test for a `SlugGenerator` service that tests the following inputs and expected outputs:
- "Hello World" → "hello-world"
- "Symfony 7.0!" → "symfony-7-0"
- "Café François" → "cafe-francois"
- "Multiple   Spaces" → "multiple-spaces"

---

### Question 8: Database Testing Strategies

**Question**: Compare and contrast three different strategies for testing with databases in Symfony. What are the pros and cons of each?

---

### Question 9: Mocking vs Stubbing

**Question**: Explain the difference between a mock and a stub. When would you use each? Provide code examples.

---

### Question 10: Testing Forms

**Question**: Write a functional test for a user registration form that tests validation errors for:
- Invalid email format
- Password too short (minimum 8 characters)
- Password confirmation doesn't match

---

### Question 11: Code Coverage

**Question**: What does 80% code coverage mean? Does it guarantee your code is bug-free? What are some limitations of code coverage metrics?

---

### Question 12: Crawler Usage

**Question**: Given the following HTML, write code using the Crawler to extract all product names and prices:

```html
<div class="products">
    <div class="product">
        <h3 class="name">Product 1</h3>
        <span class="price">$19.99</span>
    </div>
    <div class="product">
        <h3 class="name">Product 2</h3>
        <span class="price">$29.99</span>
    </div>
</div>
```

---

### Question 13: Testing Authentication

**Question**: How do you test a protected route that requires authentication in Symfony? Provide code examples for both:
- Testing that unauthenticated access is denied
- Testing that authenticated users can access the route

---

### Question 14: Test Doubles

**Question**: Explain the difference between the five types of test doubles: Dummy, Stub, Spy, Mock, and Fake. Provide a brief example of when you'd use each.

---

### Question 15: KernelTestCase

**Question**: What is `KernelTestCase` and when should you use it instead of `WebTestCase`? Provide an example.

---

### Question 16: Testing with Fixtures

**Question**: Write code to load Doctrine fixtures in a test case before running tests. How would you ensure each test starts with a clean database state?

---

### Question 17: JSON API Testing

**Question**: Write a functional test for a REST API endpoint `POST /api/posts` that:
- Sends JSON data
- Expects a 201 Created response
- Verifies the response contains an `id` field
- Checks the returned data matches what was sent

---

### Question 18: setUp and tearDown

**Question**: What is the purpose of `setUp()` and `tearDown()` methods in PHPUnit? What's the difference between `setUp()` and `setUpBeforeClass()`?

---

### Question 19: Exception Testing

**Question**: Write a test that verifies a `PriceCalculator::calculate()` method throws an `InvalidArgumentException` with the message "Price must be positive" when given a negative price.

---

### Question 20: Testing Repository Methods

**Question**: Write an integration test for a custom repository method `findActiveProductsByCategory($categoryId)` that verifies:
- It returns only active products
- All products belong to the specified category
- Results are ordered by name

---

## Answers

### Answer 1: Testing Pyramid

The testing pyramid is a strategy that helps balance different types of tests:

```
                    ▲
                   /E\
                  /2E \          End-to-End: <5%
                 /Tests\
                /_______\
               /         \
              /Functional\      Functional: 10-15%
             /   Tests    \
            /_____________\
           /               \
          /  Integration   \    Integration: 15-25%
         /     Tests       \
        /___________________\
       /                     \
      /      Unit Tests      \  Unit: 60-70%
     /_______________________\
```

**Recommended Distribution**:
- **Unit Tests**: 60-70% - Fast, isolated, test single components
- **Integration Tests**: 15-25% - Test component interactions
- **Functional Tests**: 10-15% - Test HTTP endpoints
- **E2E Tests**: <5% - Test critical user workflows

**Rationale**:
- Unit tests are fastest and cheapest to maintain
- As you go up the pyramid, tests become slower and more expensive
- More tests at the base provide faster feedback
- Fewer tests at the top cover critical user journeys

---

### Answer 2: PHPUnit Configuration

`SYMFONY_DEPRECATIONS_HELPER` controls how PHPUnit handles deprecation warnings.

**Modes**:

1. **`disabled`**: Don't track deprecations
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="disabled" />
```

2. **`weak`**: Show deprecation summary but don't fail the test suite
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="weak" />
```

3. **`0` (strict)**: Fail on any deprecation
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="0" />
```

4. **`max[self]=X`**: Allow up to X deprecations from your code
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="max[self]=10" />
```

5. **`max[direct]=X`**: Allow up to X direct deprecations (your code calling deprecated code)
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="max[direct]=5" />
```

6. **`max[indirect]=X`**: Allow up to X indirect deprecations (vendor code)
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="max[indirect]=999" />
```

7. **Combined**: Mix multiple rules
```xml
<env name="SYMFONY_DEPRECATIONS_HELPER" value="max[self]=0&max[direct]=0&max[indirect]=999" />
```

---

### Answer 3: Unit Test vs Integration Test

**Unit Test**: Tests a single class in isolation, with dependencies mocked.

```php
namespace App\Tests\Unit\Service;

use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function testCalculateWithTax(): void
    {
        // No external dependencies, pure logic
        $calculator = new PriceCalculator();

        $result = $calculator->calculateWithTax(100, 0.20);

        $this->assertEquals(120, $result);
    }
}
```

**Integration Test**: Tests multiple components working together, using real dependencies.

```php
namespace App\Tests\Integration\Service;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderServiceTest extends KernelTestCase
{
    public function testCreateOrder(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        // Get real service with real dependencies from container
        $orderService = $container->get(OrderService::class);

        // Tests interaction between OrderService, Repository, EntityManager, etc.
        $order = $orderService->createOrder([
            'customer_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 2]]
        ]);

        $this->assertNotNull($order->getId());
    }
}
```

**Key Differences**:
- Unit tests are faster, more focused, use mocks
- Integration tests are slower, test real interactions, use real services

---

### Answer 4: Mock Expectations

```php
namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testActivateUserSuccess(): void
    {
        // Create mock repository
        $repository = $this->createMock(UserRepository::class);

        // Create user object to return
        $user = new User();
        $user->setActive(false);

        // Set expectations
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->isActive() === true;
            }));

        // Create service with mock
        $service = new UserService($repository);

        // Execute
        $result = $service->activateUser(1);

        // Assert
        $this->assertTrue($result);
        $this->assertTrue($user->isActive());
    }

    public function testActivateUserNotFound(): void
    {
        $repository = $this->createMock(UserRepository::class);

        $repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        // Should never call save if user not found
        $repository->expects($this->never())
            ->method('save');

        $service = new UserService($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $service->activateUser(999);
    }
}
```

---

### Answer 5: WebTestCase Assertions

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BlogPostTest extends WebTestCase
{
    public function testShowBlogPost(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/blog/my-blog-post');

        // 1. Response is successful (200)
        $this->assertResponseIsSuccessful();

        // 2. Page title contains "My Blog Post"
        $this->assertPageTitleContains('My Blog Post');

        // 3. Exactly one h1 element
        $this->assertCount(1, $crawler->filter('h1'));

        // 4. Post content div exists
        $this->assertSelectorExists('.post-content');

        // 5. Author name is "John Doe"
        $this->assertSelectorTextContains('.author', 'John Doe');
    }
}
```

---

### Answer 6: Test Client Methods

**`$client->request()`**: Makes a direct HTTP request to a URL.

```php
// Use when: Testing a specific endpoint directly
$crawler = $client->request('GET', '/posts');
$client->request('POST', '/api/posts', ['title' => 'New Post']);
```

**`$client->clickLink()`**: Finds and clicks a link by its text.

```php
// Use when: Simulating user clicking a link
$crawler = $client->request('GET', '/');
$client->clickLink('Read More'); // Finds <a>Read More</a> and clicks it
```

**`$client->submit()`**: Submits a form.

```php
// Use when: Testing form submissions
$crawler = $client->request('GET', '/contact');
$form = $crawler->selectButton('Send')->form([
    'contact[email]' => 'user@example.com',
    'contact[message]' => 'Hello',
]);
$client->submit($form);
```

**Summary**:
- `request()` - Direct HTTP calls
- `clickLink()` - Simulate clicking links
- `submit()` - Simulate form submissions

---

### Answer 7: Data Providers

```php
namespace App\Tests\Unit\Service;

use App\Service\SlugGenerator;
use PHPUnit\Framework\TestCase;

class SlugGeneratorTest extends TestCase
{
    /**
     * @dataProvider slugProvider
     */
    public function testGenerate(string $input, string $expected): void
    {
        $generator = new SlugGenerator();
        $result = $generator->generate($input);

        $this->assertEquals($expected, $result);
    }

    public static function slugProvider(): array
    {
        return [
            'basic words' => ['Hello World', 'hello-world'],
            'with numbers' => ['Symfony 7.0!', 'symfony-7-0'],
            'unicode characters' => ['Café François', 'cafe-francois'],
            'multiple spaces' => ['Multiple   Spaces', 'multiple-spaces'],
        ];
    }
}
```

**Alternative using Generator**:

```php
public static function slugProvider(): iterable
{
    yield 'basic words' => ['Hello World', 'hello-world'];
    yield 'with numbers' => ['Symfony 7.0!', 'symfony-7-0'];
    yield 'unicode characters' => ['Café François', 'cafe-francois'];
    yield 'multiple spaces' => ['Multiple   Spaces', 'multiple-spaces'];
}
```

---

### Answer 8: Database Testing Strategies

**Strategy 1: Test Database with Fixtures**

```php
protected function setUp(): void
{
    $loader = new Loader();
    $loader->addFixture(new UserFixtures());

    $purger = new ORMPurger($this->entityManager);
    $executor = new ORMExecutor($this->entityManager, $purger);
    $executor->execute($loader->getFixtures());
}
```

**Pros**:
- Realistic data
- Reusable fixtures
- Can test with complex data

**Cons**:
- Slower
- Need to maintain fixtures
- Database state shared between tests

---

**Strategy 2: Database Transactions**

```php
protected function setUp(): void
{
    $this->connection->beginTransaction();
}

protected function tearDown(): void
{
    $this->connection->rollBack();
}
```

**Pros**:
- Fast rollback
- Clean state per test
- No need to recreate database

**Cons**:
- Cannot test transactions themselves
- Still uses database I/O

---

**Strategy 3: In-Memory SQLite**

```yaml
# config/packages/test/doctrine.yaml
doctrine:
    dbal:
        url: 'sqlite:///:memory:'
```

**Pros**:
- Very fast
- Fresh database per run
- No external database needed

**Cons**:
- SQLite behaves differently from PostgreSQL/MySQL
- Cannot test database-specific features
- Schema created each time

---

### Answer 9: Mocking vs Stubbing

**Stub**: Returns predetermined values, no expectations about how it's called.

```php
public function testWithStub(): void
{
    // Stub just returns values
    $stub = $this->createStub(MailerInterface::class);
    $stub->method('send')->willReturn(true);

    $service = new NotificationService($stub);
    $result = $service->sendEmail($email);

    // We don't verify if send() was called
    $this->assertTrue($result);
}
```

**Use stubs when**: You need a dependency to return specific values but don't care how it's called.

---

**Mock**: Sets expectations and verifies method calls.

```php
public function testWithMock(): void
{
    // Mock verifies behavior
    $mock = $this->createMock(MailerInterface::class);

    $mock->expects($this->once())
        ->method('send')
        ->with($this->isInstanceOf(Email::class))
        ->willReturn(true);

    $service = new NotificationService($mock);
    $service->sendEmail($email);

    // Mock automatically verifies send() was called once
}
```

**Use mocks when**: You need to verify that specific methods are called with specific arguments.

---

### Answer 10: Testing Forms

```php
namespace App\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRegistrationValidationTest extends WebTestCase
{
    public function testInvalidEmailFormat(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration[email]' => 'invalid-email-format',
            'registration[password][first]' => 'ValidPass123',
            'registration[password][second]' => 'ValidPass123',
        ]);

        $client->submit($form);

        $this->assertResponseIsUnprocessable(); // 422
        $this->assertSelectorExists('.invalid-feedback');
        $this->assertSelectorTextContains(
            '.email .invalid-feedback',
            'valid email'
        );
    }

    public function testPasswordTooShort(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration[email]' => 'user@example.com',
            'registration[password][first]' => 'short',  // Less than 8 chars
            'registration[password][second]' => 'short',
        ]);

        $client->submit($form);

        $this->assertResponseIsUnprocessable();
        $this->assertSelectorTextContains(
            '.password .invalid-feedback',
            'at least 8 characters'
        );
    }

    public function testPasswordConfirmationMismatch(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Register')->form([
            'registration[email]' => 'user@example.com',
            'registration[password][first]' => 'Password123',
            'registration[password][second]' => 'DifferentPass123',  // Mismatch
        ]);

        $client->submit($form);

        $this->assertResponseIsUnprocessable();
        $this->assertSelectorTextContains(
            '.password .invalid-feedback',
            'match'
        );
    }
}
```

---

### Answer 11: Code Coverage

**What 80% Coverage Means**:
80% of your code lines are executed during test runs.

```
Example:
Total lines: 100
Lines executed: 80
Coverage: 80%
```

**Does it Guarantee Bug-Free Code?**
**No!** Code coverage measures what code is executed, not whether it's tested correctly.

**Example of 100% Coverage with Poor Testing**:

```php
public function divide(int $a, int $b): int
{
    return $a / $b;  // Bug: Division by zero!
}

// This test has 100% coverage but misses the bug
public function testDivide(): void
{
    $result = $this->calculator->divide(10, 2);
    $this->assertEquals(5, $result); // Passes, but didn't test $b = 0
}
```

**Limitations**:
1. **Doesn't test edge cases**: Code might run but not be tested for all scenarios
2. **Doesn't verify correctness**: Code executes but assertions might be wrong/missing
3. **Doesn't test logic paths**: Might not test all if/else branches properly
4. **False security**: High coverage doesn't mean high quality tests

**Good Practice**:
- Aim for 70-80% coverage
- Focus on critical business logic
- Write meaningful assertions
- Test edge cases and error conditions
- Quality over quantity

---

### Answer 12: Crawler Usage

```php
public function testExtractProducts(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/products');

    // Extract products as array of arrays
    $products = $crawler->filter('.product')->each(function ($node) {
        return [
            'name' => $node->filter('.name')->text(),
            'price' => $node->filter('.price')->text(),
        ];
    });

    $this->assertCount(2, $products);
    $this->assertEquals('Product 1', $products[0]['name']);
    $this->assertEquals('$19.99', $products[0]['price']);
    $this->assertEquals('Product 2', $products[1]['name']);
    $this->assertEquals('$29.99', $products[1]['price']);
}

// Alternative: Extract just names
public function testExtractProductNames(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/products');

    $names = $crawler->filter('.product .name')->each(function ($node) {
        return $node->text();
    });

    $this->assertEquals(['Product 1', 'Product 2'], $names);
}

// Alternative: Extract just prices
public function testExtractProductPrices(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/products');

    $prices = $crawler->filter('.product .price')->each(function ($node) {
        return $node->text();
    });

    $this->assertEquals(['$19.99', '$29.99'], $prices);
}
```

---

### Answer 13: Testing Authentication

**Test 1: Unauthenticated Access is Denied**

```php
namespace App\Tests\Functional\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProtectedRouteTest extends WebTestCase
{
    public function testProtectedRouteRequiresAuthentication(): void
    {
        $client = static::createClient();

        // Try to access protected route without authentication
        $client->request('GET', '/admin/dashboard');

        // Should redirect to login
        $this->assertResponseRedirects('/login');

        // Or check for 401/403 if using API
        // $this->assertResponseStatusCodeSame(401);
    }
}
```

**Test 2: Authenticated Users Can Access**

```php
namespace App\Tests\Functional\Security;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthenticatedAccessTest extends WebTestCase
{
    public function testAuthenticatedUserCanAccessProtectedRoute(): void
    {
        $client = static::createClient();

        // Get user from database
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin@example.com']);

        // Login user programmatically
        $client->loginUser($user);

        // Now can access protected route
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Admin Dashboard');
    }

    public function testUserWithoutRoleCannotAccessAdminRoute(): void
    {
        $client = static::createClient();

        // Login as regular user (not admin)
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user@example.com']);

        $client->loginUser($user);

        // Try to access admin route
        $client->request('GET', '/admin/dashboard');

        // Should be forbidden
        $this->assertResponseStatusCodeSame(403);
    }
}
```

**Test 3: Manual Login Flow**

```php
public function testLoginFlow(): void
{
    $client = static::createClient();

    // Visit login page
    $crawler = $client->request('GET', '/login');

    // Submit login form
    $form = $crawler->selectButton('Sign in')->form([
        '_username' => 'admin@example.com',
        '_password' => 'password',
    ]);

    $client->submit($form);

    // Should redirect to dashboard
    $this->assertResponseRedirects('/dashboard');
    $crawler = $client->followRedirect();

    // Now authenticated, can access protected routes
    $client->request('GET', '/admin/dashboard');
    $this->assertResponseIsSuccessful();
}
```

---

### Answer 14: Test Doubles

**1. Dummy**: Passed but never actually used.

```php
// Example: Logger is required but not used in this test
public function testWithDummy(): void
{
    $dummy = $this->createStub(LoggerInterface::class);

    $service = new Calculator($dummy); // Logger required but not called
    $result = $service->add(2, 3);

    $this->assertEquals(5, $result);
}
```

**When to use**: Object is required by interface but not needed for the test.

---

**2. Stub**: Returns pre-programmed responses.

```php
// Example: Repository that returns fixed data
public function testWithStub(): void
{
    $stub = $this->createStub(UserRepository::class);
    $stub->method('find')->willReturn(new User('john@example.com'));

    $service = new UserService($stub);
    $user = $service->getUser(1);

    $this->assertEquals('john@example.com', $user->getEmail());
}
```

**When to use**: Need dependency to return specific values.

---

**3. Spy**: Records information about how it was called.

```php
// Example: Verify method was called with correct arguments
public function testWithSpy(): void
{
    $spy = $this->createMock(AuditLogger::class);

    $spy->expects($this->once())
        ->method('log')
        ->with('user_created', $this->anything());

    $service = new UserService($spy);
    $service->createUser(['email' => 'user@example.com']);

    // Spy verifies log() was called
}
```

**When to use**: Need to verify method calls without changing behavior.

---

**4. Mock**: Pre-programmed with expectations that verify behavior.

```php
// Example: Strict expectations about calls
public function testWithMock(): void
{
    $mock = $this->createMock(PaymentGateway::class);

    $mock->expects($this->once())
        ->method('charge')
        ->with(100.00)
        ->willReturn(true);

    $service = new PaymentService($mock);
    $service->processPayment(100.00);

    // Mock verifies charge() was called once with 100.00
}
```

**When to use**: Need to verify specific interactions and behavior.

---

**5. Fake**: Working implementation but simplified (not production-ready).

```php
// Example: In-memory repository instead of database
class FakeUserRepository implements UserRepositoryInterface
{
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->getId()] = $user;
    }

    public function find(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }
}

public function testWithFake(): void
{
    $fake = new FakeUserRepository();

    $service = new UserService($fake);
    $user = $service->createUser(['email' => 'user@example.com']);

    $this->assertNotNull($user->getId());
    $this->assertEquals($user, $fake->find($user->getId()));
}
```

**When to use**: Need working implementation but don't want real infrastructure.

---

### Answer 15: KernelTestCase

**What is KernelTestCase?**

`KernelTestCase` boots the Symfony kernel and provides access to the service container, but doesn't create an HTTP client.

**When to Use It**:
- Testing services that need dependency injection
- Testing repositories with real database
- Testing console commands
- Integration tests without HTTP layer

**When to Use WebTestCase Instead**:
- Testing controllers and HTTP endpoints
- Testing full request/response cycle
- Functional tests that need HTTP client

**Example**:

```php
namespace App\Tests\Integration\Service;

use App\Service\ReportGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportGeneratorTest extends KernelTestCase
{
    private ReportGenerator $reportGenerator;

    protected function setUp(): void
    {
        // Boot the kernel
        self::bootKernel();

        // Get service from container
        $container = static::getContainer();
        $this->reportGenerator = $container->get(ReportGenerator::class);
    }

    public function testGenerateMonthlyReport(): void
    {
        // Test service with real dependencies
        $report = $this->reportGenerator->generate('monthly', [
            'month' => '2024-01',
        ]);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertNotEmpty($report->getData());
    }
}
```

**Key Differences**:

```php
// KernelTestCase - For services and integration tests
class ServiceTest extends KernelTestCase
{
    public function test(): void
    {
        self::bootKernel();
        $service = static::getContainer()->get(MyService::class);
        // Test service...
    }
}

// WebTestCase - For controllers and HTTP tests
class ControllerTest extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $client->request('GET', '/endpoint');
        // Test HTTP response...
    }
}
```

---

### Answer 16: Testing with Fixtures

```php
namespace App\Tests\Integration;

use App\DataFixtures\UserFixtures;
use App\DataFixtures\PostFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class FixtureTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // Load fixtures before each test
        $this->loadFixtures();
    }

    protected function loadFixtures(): void
    {
        // Create fixture loader
        $loader = new Loader();

        // Add fixtures
        $loader->addFixture(new UserFixtures());
        $loader->addFixture(new PostFixtures());

        // Create purger to clean database
        $purger = new ORMPurger($this->entityManager);

        // Create executor
        $executor = new ORMExecutor($this->entityManager, $purger);

        // Execute fixtures (purges then loads)
        $executor->execute($loader->getFixtures());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Close entity manager to avoid memory leaks
        $this->entityManager->close();
    }
}

// Usage in actual test
class UserRepositoryTest extends FixtureTestCase
{
    public function testFindActiveUsers(): void
    {
        // Fixtures are loaded, database has known state
        $repository = $this->entityManager->getRepository(User::class);

        $users = $repository->findBy(['active' => true]);

        $this->assertNotEmpty($users);
    }
}
```

**Alternative: Using Transactions for Clean State**

```php
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

        // Load fixtures once
        $this->loadFixtures();

        // Start transaction
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback changes - each test starts with same state
        $this->connection->rollBack();

        $this->entityManager->close();
        parent::tearDown();
    }
}
```

---

### Answer 17: JSON API Testing

```php
namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostApiTest extends WebTestCase
{
    public function testCreatePost(): void
    {
        $client = static::createClient();

        // Prepare JSON data
        $postData = [
            'title' => 'API Test Post',
            'content' => 'This post was created via API',
            'published' => true,
        ];

        // Make POST request with JSON
        $client->request(
            'POST',
            '/api/posts',
            [],                                              // parameters
            [],                                              // files
            ['CONTENT_TYPE' => 'application/json'],         // server/headers
            json_encode($postData)                           // content
        );

        // Verify 201 Created response
        $this->assertResponseStatusCodeSame(201);

        // Verify response is JSON
        $this->assertResponseHeaderSame(
            'Content-Type',
            'application/json'
        );

        // Decode response
        $responseData = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        // Verify response contains ID
        $this->assertArrayHasKey('id', $responseData);
        $this->assertIsInt($responseData['id']);

        // Verify returned data matches sent data
        $this->assertEquals($postData['title'], $responseData['title']);
        $this->assertEquals($postData['content'], $responseData['content']);
        $this->assertEquals($postData['published'], $responseData['published']);

        // Additional verifications
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertNotEmpty($responseData['createdAt']);
    }

    public function testCreatePostWithInvalidData(): void
    {
        $client = static::createClient();

        // Send invalid data (missing required fields)
        $client->request(
            'POST',
            '/api/posts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => '']) // Invalid: empty title
        );

        // Verify 400 Bad Request or 422 Unprocessable Entity
        $this->assertResponseStatusCodeSame(422);

        // Verify error response
        $responseData = json_decode(
            $client->getResponse()->getContent(),
            true
        );

        $this->assertArrayHasKey('errors', $responseData);
        $this->assertNotEmpty($responseData['errors']);
    }
}
```

---

### Answer 18: setUp and tearDown

**`setUp()`**: Runs before each test method.

```php
class ExampleTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        parent::setUp(); // Always call parent

        // Initialize dependencies for each test
        $this->calculator = new Calculator();
    }

    public function testAdd(): void
    {
        // setUp() already ran, calculator is ready
        $result = $this->calculator->add(2, 3);
        $this->assertEquals(5, $result);
    }

    public function testSubtract(): void
    {
        // setUp() runs again, fresh calculator instance
        $result = $this->calculator->subtract(5, 3);
        $this->assertEquals(2, $result);
    }
}
```

**`tearDown()`**: Runs after each test method.

```php
class DatabaseTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = /* get from container */;
    }

    protected function tearDown(): void
    {
        // Clean up after test
        $this->entityManager->close();
        $this->entityManager = null;

        parent::tearDown(); // Always call parent
    }
}
```

**`setUpBeforeClass()`**: Runs once before all tests in class.

```php
class DatabaseSchemaTest extends KernelTestCase
{
    private static EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Create database schema once for all tests
        $kernel = self::bootKernel();
        self::$entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $schemaTool = new SchemaTool(self::$entityManager);
        $metadata = self::$entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up after all tests
        self::$entityManager->close();
        parent::tearDownAfterClass();
    }
}
```

**Execution Order**:

```
setUpBeforeClass()
    setUp()
        test1()
    tearDown()

    setUp()
        test2()
    tearDown()

    setUp()
        test3()
    tearDown()
tearDownAfterClass()
```

**Key Differences**:
- `setUp()`/`tearDown()`: Per test (most common)
- `setUpBeforeClass()`/`tearDownAfterClass()`: Once per class (expensive operations)

---

### Answer 19: Exception Testing

```php
namespace App\Tests\Unit\Service;

use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorExceptionTest extends TestCase
{
    public function testNegativePriceThrowsException(): void
    {
        $calculator = new PriceCalculator();

        // Set exception expectations BEFORE the code that throws
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');

        // This should throw the exception
        $calculator->calculate(-10);

        // Code after exception is never executed
    }

    // Alternative: Test exception code
    public function testExceptionCode(): void
    {
        $calculator = new PriceCalculator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price must be positive');
        $this->expectExceptionCode(400);

        $calculator->calculate(-10);
    }

    // Alternative: Catch and verify exception
    public function testExceptionWithCatch(): void
    {
        $calculator = new PriceCalculator();

        try {
            $calculator->calculate(-10);
            $this->fail('Exception should have been thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Price must be positive', $e->getMessage());
            $this->assertEquals(400, $e->getCode());
        }
    }

    // Alternative: Regex message matching
    public function testExceptionMessageMatches(): void
    {
        $calculator = new PriceCalculator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/price.*positive/i');

        $calculator->calculate(-10);
    }
}
```

---

### Answer 20: Testing Repository Methods

```php
namespace App\Tests\Integration\Repository;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->repository = $kernel->getContainer()
            ->get(ProductRepository::class);
    }

    public function testFindActiveProductsByCategory(): void
    {
        $categoryId = 1; // Assuming category exists in fixtures/database

        // Execute repository method
        $products = $this->repository->findActiveProductsByCategory($categoryId);

        // Verify results are not empty
        $this->assertNotEmpty($products, 'Should return products for category');

        // Verify all products are active
        foreach ($products as $product) {
            $this->assertTrue(
                $product->isActive(),
                sprintf('Product %d should be active', $product->getId())
            );
        }

        // Verify all products belong to specified category
        foreach ($products as $product) {
            $this->assertEquals(
                $categoryId,
                $product->getCategory()->getId(),
                sprintf('Product %d should belong to category %d',
                    $product->getId(),
                    $categoryId
                )
            );
        }

        // Verify results are ordered by name
        $names = array_map(fn($p) => $p->getName(), $products);
        $sortedNames = $names;
        sort($sortedNames);

        $this->assertEquals(
            $sortedNames,
            $names,
            'Products should be ordered by name'
        );
    }

    public function testFindActiveProductsByCategoryReturnsEmptyForInactiveProducts(): void
    {
        // Test with category that only has inactive products
        $categoryWithInactiveProducts = 99;

        $products = $this->repository->findActiveProductsByCategory(
            $categoryWithInactiveProducts
        );

        $this->assertEmpty($products, 'Should not return inactive products');
    }

    public function testFindActiveProductsByCategoryReturnsEmptyForNonexistentCategory(): void
    {
        $nonexistentCategoryId = 9999;

        $products = $this->repository->findActiveProductsByCategory(
            $nonexistentCategoryId
        );

        $this->assertEmpty($products, 'Should return empty array for nonexistent category');
    }
}
```

**The Repository Method Being Tested**:

```php
namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findActiveProductsByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.active = :active')
            ->andWhere('p.category = :category')
            ->setParameter('active', true)
            ->setParameter('category', $categoryId)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

---

## Summary

These questions cover the key concepts in Symfony testing:
- Different test types and when to use them
- PHPUnit configuration and features
- Mocking and test doubles
- Functional testing with WebTestCase
- Integration testing with KernelTestCase
- Database testing strategies
- Form testing
- API testing
- Code coverage
- Best practices

Practice these concepts to build robust test suites for your Symfony applications!
