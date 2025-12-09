# Deep Dive: Advanced Controller Topics

Explore advanced controller patterns and techniques in Symfony.

---

## Table of Contents

1. [Custom Argument Value Resolvers](#custom-argument-value-resolvers)
2. [Controller as a Service](#controller-as-a-service)
3. [Invokable Controllers](#invokable-controllers)
4. [Streaming Responses](#streaming-responses)
5. [Content Negotiation in Controllers](#content-negotiation-in-controllers)

---

## Custom Argument Value Resolvers

Create sophisticated argument resolvers for specialized use cases.

### Basic Custom Resolver

```php
// src/ArgumentResolver/PaginationResolver.php
namespace App\ArgumentResolver;

use App\Model\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class PaginationResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only resolve Pagination type
        if ($argument->getType() !== Pagination::class) {
            return [];
        }

        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'asc');

        // Validate and sanitize
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage)); // Cap at 100
        $order = in_array(strtolower($order), ['asc', 'desc']) ? strtolower($order) : 'asc';

        yield new Pagination($page, $perPage, $sort, $order);
    }
}

// src/Model/Pagination.php
namespace App\Model;

class Pagination
{
    public function __construct(
        private int $page,
        private int $perPage,
        private string $sort,
        private string $order,
    ) {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function getSort(): string
    {
        return $this->sort;
    }

    public function getOrder(): string
    {
        return $this->order;
    }
}

// Usage in controller
use App\Model\Pagination;

#[Route('/products')]
public function list(Pagination $pagination): Response
{
    $products = $this->productRepository->findAllPaginated(
        $pagination->getOffset(),
        $pagination->getPerPage(),
        $pagination->getSort(),
        $pagination->getOrder()
    );

    return $this->render('product/list.html.twig', [
        'products' => $products,
        'pagination' => $pagination,
    ]);
}
```

### Resolver with Attribute-Based Configuration

```php
// src/Attribute/CurrentTenant.php
namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class CurrentTenant
{
    public function __construct(
        public readonly bool $required = true,
    ) {}
}

// src/ArgumentResolver/CurrentTenantResolver.php
namespace App\ArgumentResolver;

use App\Attribute\CurrentTenant;
use App\Entity\Tenant;
use App\Service\TenantContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CurrentTenantResolver implements ValueResolverInterface
{
    public function __construct(
        private TenantContext $tenantContext,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Get the attribute
        $attributes = $argument->getAttributes(CurrentTenant::class, ArgumentMetadata::IS_INSTANCEOF);

        if (empty($attributes)) {
            return [];
        }

        /** @var CurrentTenant $attribute */
        $attribute = $attributes[0];

        // Check type
        if ($argument->getType() !== Tenant::class && $argument->getType() !== '?' . Tenant::class) {
            throw new \LogicException(
                '#[CurrentTenant] can only be used with Tenant or ?Tenant type'
            );
        }

        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant && $attribute->required && !$argument->isNullable()) {
            throw new BadRequestHttpException('Tenant is required but not set');
        }

        yield $tenant;
    }
}

// Usage
use App\Attribute\CurrentTenant;
use App\Entity\Tenant;

#[Route('/dashboard')]
public function dashboard(
    #[CurrentTenant] Tenant $tenant
): Response {
    return $this->render('dashboard.html.twig', [
        'tenant' => $tenant,
    ]);
}

#[Route('/public')]
public function public(
    #[CurrentTenant(required: false)] ?Tenant $tenant
): Response {
    // $tenant can be null
    return $this->render('public.html.twig', [
        'tenant' => $tenant,
    ]);
}
```

### Resolver with Validation

```php
// src/Attribute/ValidatedBody.php
namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ValidatedBody
{
    public function __construct(
        public readonly array $validationGroups = ['Default'],
        public readonly string $format = 'json',
    ) {}
}

// src/ArgumentResolver/ValidatedBodyResolver.php
namespace App\ArgumentResolver;

use App\Attribute\ValidatedBody;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidatedBodyResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attributes = $argument->getAttributes(ValidatedBody::class, ArgumentMetadata::IS_INSTANCEOF);

        if (empty($attributes)) {
            return [];
        }

        /** @var ValidatedBody $attribute */
        $attribute = $attributes[0];

        $type = $argument->getType();
        if (!$type || !class_exists($type)) {
            throw new \LogicException(
                '#[ValidatedBody] requires a valid class type'
            );
        }

        // Deserialize
        try {
            $object = $this->serializer->deserialize(
                $request->getContent(),
                $type,
                $attribute->format
            );
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid request body: ' . $e->getMessage());
        }

        // Validate
        $violations = $this->validator->validate($object, null, $attribute->validationGroups);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            throw new BadRequestHttpException(
                'Validation failed: ' . json_encode($errors)
            );
        }

        yield $object;
    }
}

// Usage
use App\Attribute\ValidatedBody;
use App\Dto\CreateProductDto;

#[Route('/api/products', methods: ['POST'])]
public function create(
    #[ValidatedBody(validationGroups: ['create'])]
    CreateProductDto $dto
): JsonResponse {
    // $dto is already validated
    $product = $this->productService->create($dto);

    return $this->json($product, 201);
}
```

### Resolver with Dependency on Other Resolvers

```php
// src/ArgumentResolver/EntityFromBodyResolver.php
namespace App\ArgumentResolver;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Resolves entity from JSON body using 'id' field
 */
class EntityFromBodyResolver implements ValueResolverInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $entityManager,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        // Only for Doctrine entities
        if (!$type || !class_exists($type)) {
            return [];
        }

        $metadata = $this->entityManager->getClassMetadata($type);
        if (!$metadata) {
            return [];
        }

        // Only for POST/PUT/PATCH with JSON
        if (!$request->isMethod('POST') && !$request->isMethod('PUT') && !$request->isMethod('PATCH')) {
            return [];
        }

        if ($request->getContentType() !== 'json') {
            return [];
        }

        try {
            $data = $request->toArray();
        } catch (\Exception $e) {
            return [];
        }

        // Look for 'id' field
        if (!isset($data['id'])) {
            return [];
        }

        $entity = $this->entityManager->find($type, $data['id']);

        if (!$entity) {
            throw new NotFoundHttpException(sprintf(
                '%s with id %s not found',
                $type,
                $data['id']
            ));
        }

        // Merge updates into entity
        $this->serializer->deserialize(
            $request->getContent(),
            $type,
            'json',
            ['object_to_populate' => $entity]
        );

        yield $entity;
    }
}
```

### Advanced: Composite Resolver

```php
// src/ArgumentResolver/ApiRequestResolver.php
namespace App\ArgumentResolver;

use App\Model\ApiRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== ApiRequest::class) {
            return [];
        }

        // Extract API version from header
        $version = $request->headers->get('X-API-Version', '1.0');

        // Extract authentication
        $authHeader = $request->headers->get('Authorization');
        $apiKey = null;

        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $apiKey = substr($authHeader, 7);
        }

        // Extract pagination
        $page = $request->query->getInt('page', 1);
        $perPage = $request->query->getInt('per_page', 20);

        // Extract filters
        $filters = $request->query->all('filter');

        // Extract sorting
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'asc');

        // Parse request body
        $body = null;
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            try {
                $body = $request->toArray();
            } catch (\Exception $e) {
                throw new BadRequestHttpException('Invalid JSON body');
            }
        }

        yield new ApiRequest(
            version: $version,
            apiKey: $apiKey,
            page: max(1, $page),
            perPage: min(100, max(1, $perPage)),
            filters: $filters,
            sort: $sort,
            order: in_array($order, ['asc', 'desc']) ? $order : 'asc',
            body: $body,
            request: $request,
        );
    }
}

// src/Model/ApiRequest.php
namespace App\Model;

use Symfony\Component\HttpFoundation\Request;

class ApiRequest
{
    public function __construct(
        public readonly string $version,
        public readonly ?string $apiKey,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $filters,
        public readonly string $sort,
        public readonly string $order,
        public readonly ?array $body,
        public readonly Request $request,
    ) {}

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasFilter(string $key): bool
    {
        return isset($this->filters[$key]);
    }

    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    public function isAuthenticated(): bool
    {
        return $this->apiKey !== null;
    }
}

// Usage
use App\Model\ApiRequest;

#[Route('/api/products')]
public function apiList(ApiRequest $apiRequest): JsonResponse
{
    if (!$apiRequest->isAuthenticated()) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    $products = $this->productRepository->findByApiRequest($apiRequest);

    return $this->json([
        'version' => $apiRequest->version,
        'data' => $products,
        'meta' => [
            'page' => $apiRequest->page,
            'per_page' => $apiRequest->perPage,
        ],
    ]);
}
```

---

## Controller as a Service

Configure controllers as explicit services for better control and testing.

### Why Controller as Service?

```php
// Traditional approach - automatic service configuration
class ProductController extends AbstractController
{
    // Services injected via autowiring in methods
    #[Route('/products')]
    public function list(ProductRepository $repository): Response
    {
        // ...
    }
}

// Controller as service - explicit dependencies
class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $repository,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    #[Route('/products')]
    public function list(): Response
    {
        // Services always available
        $this->logger->info('Listing products');
        // ...
    }
}
```

### Benefits of Controller as Service

```php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Analytics;
use App\Service\CacheManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private Analytics $analytics,
        private CacheManager $cacheManager,
        private LoggerInterface $logger,
    ) {}

    #[Route('/products')]
    public function list(): Response
    {
        // Benefits:
        // 1. All dependencies clearly visible
        // 2. Easy to test (mock in constructor)
        // 3. Enforce dependencies (can't create without them)
        // 4. Better IDE support
        // 5. Reusable across actions

        $cacheKey = 'product_list';

        // Use injected services
        if ($cached = $this->cacheManager->get($cacheKey)) {
            $this->analytics->track('cache_hit', ['key' => $cacheKey]);
            return $cached;
        }

        $products = $this->productRepository->findAllActive();

        $this->analytics->track('products_listed', [
            'count' => count($products),
        ]);

        $response = $this->render('product/list.html.twig', [
            'products' => $products,
        ]);

        $this->cacheManager->set($cacheKey, $response, 3600);

        return $response;
    }

    #[Route('/products/{id}')]
    public function show(int $id): Response
    {
        // Services already available, no need to inject in every method
        $product = $this->productRepository->find($id);

        if (!$product) {
            $this->logger->warning('Product not found', ['id' => $id]);
            throw $this->createNotFoundException();
        }

        $this->analytics->track('product_viewed', [
            'product_id' => $id,
        ]);

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

### Testing Controller as Service

```php
// tests/Controller/ProductControllerTest.php
namespace App\Tests\Controller;

use App\Controller\ProductController;
use App\Repository\ProductRepository;
use App\Service\Analytics;
use App\Service\CacheManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductControllerTest extends TestCase
{
    public function testList(): void
    {
        // Easy to mock dependencies
        $repository = $this->createMock(ProductRepository::class);
        $repository->method('findAllActive')->willReturn([]);

        $analytics = $this->createMock(Analytics::class);
        $analytics->expects($this->once())
            ->method('track')
            ->with('products_listed', $this->anything());

        $cache = $this->createMock(CacheManager::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Create controller with mocks
        $controller = new ProductController(
            $repository,
            $analytics,
            $cache,
            $logger
        );

        // Test
        $response = $controller->list();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### Explicit Service Configuration

```yaml
# config/services.yaml
services:
    # Default auto-configuration for all controllers
    App\Controller\:
        resource: '../src/Controller/'
        tags: ['controller.service_arguments']

    # Explicit configuration for specific controller
    App\Controller\AdminController:
        arguments:
            $adminEmail: '%env(ADMIN_EMAIL)%'
            $uploadDir: '%kernel.project_dir%/var/uploads'
        calls:
            - setLogger: ['@logger']
        tags:
            - { name: 'monolog.logger', channel: 'admin' }
```

### Service Locator Pattern for Optional Services

```php
namespace App\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class FlexibleController extends AbstractController implements ServiceSubscriberInterface
{
    public function __construct(
        private ContainerInterface $locator,
    ) {}

    public static function getSubscribedServices(): array
    {
        return [
            'product.repository' => ProductRepository::class,
            'logger' => LoggerInterface::class,
            'analytics' => '?' . Analytics::class, // Optional
        ];
    }

    #[Route('/products')]
    public function list(): Response
    {
        // Get service from locator
        $repository = $this->locator->get('product.repository');
        $products = $repository->findAll();

        // Optional service
        if ($this->locator->has('analytics')) {
            $this->locator->get('analytics')->track('products_listed');
        }

        return $this->render('product/list.html.twig', [
            'products' => $products,
        ]);
    }
}
```

---

## Invokable Controllers

Single-action controllers using the `__invoke()` method.

### Basic Invokable Controller

```php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/about', name: 'about')]
class AboutController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('about.html.twig');
    }
}
```

### Why Use Invokable Controllers?

```php
// Benefits:
// 1. Single Responsibility - One controller, one action
// 2. Cleaner routing - Controller class IS the action
// 3. Better organization - Easier to find and maintain
// 4. Explicit dependencies - Clear what each action needs

namespace App\Controller\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Instead of: ProductController::show()
// Use: ShowProductController::__invoke()

#[Route('/product/{id}', name: 'product_show')]
class ShowProductController extends AbstractController
{
    public function __construct(
        private ProductRepository $repository,
    ) {}

    public function __invoke(int $id): Response
    {
        $product = $this->repository->find($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

### Organizing Invokable Controllers

```
src/Controller/
├── Product/
│   ├── ListProductsController.php
│   ├── ShowProductController.php
│   ├── CreateProductController.php
│   ├── UpdateProductController.php
│   └── DeleteProductController.php
├── User/
│   ├── RegisterUserController.php
│   ├── LoginController.php
│   └── LogoutController.php
└── Api/
    └── Product/
        ├── GetProductsController.php
        └── CreateProductController.php
```

### Complex Invokable Controller

```php
namespace App\Controller\Order;

use App\Dto\CreateOrderDto;
use App\Entity\User;
use App\Service\OrderService;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders', name: 'api_order_create', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class CreateOrderController extends AbstractController
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(
        #[MapRequestPayload] CreateOrderDto $dto,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $this->logger->info('Creating order', [
            'user_id' => $user->getId(),
            'items_count' => count($dto->items),
        ]);

        try {
            // Begin transaction
            $this->entityManager->beginTransaction();

            // Create order
            $order = $this->orderService->create($user, $dto);

            // Process payment
            $payment = $this->paymentService->process($order, $dto->paymentMethod);

            if (!$payment->isSuccessful()) {
                throw new \RuntimeException('Payment failed: ' . $payment->getError());
            }

            $order->setPaymentId($payment->getId());
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Order created successfully', [
                'order_id' => $order->getId(),
                'payment_id' => $payment->getId(),
            ]);

            return $this->json($order, 201);

        } catch (\Exception $e) {
            $this->entityManager->rollback();

            $this->logger->error('Order creation failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Failed to create order',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

### Invokable Controller with Events

```php
namespace App\Controller\Article;

use App\Entity\Article;
use App\Event\ArticlePublishedEvent;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/article/{id}/publish', name: 'article_publish', methods: ['POST'])]
#[IsGranted('PUBLISH', subject: 'article')]
class PublishArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(Article $article): Response
    {
        if ($article->isPublished()) {
            $this->addFlash('warning', 'Article is already published');
            return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
        }

        $article->setPublished(true);
        $article->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Dispatch event for subscribers to handle
        // (send notifications, update search index, etc.)
        $this->eventDispatcher->dispatch(
            new ArticlePublishedEvent($article),
            ArticlePublishedEvent::NAME
        );

        $this->addFlash('success', 'Article published successfully!');

        return $this->redirectToRoute('article_show', [
            'id' => $article->getId(),
        ]);
    }
}
```

### Testing Invokable Controllers

```php
namespace App\Tests\Controller\Product;

use App\Controller\Product\ShowProductController;
use App\Entity\Product;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowProductControllerTest extends TestCase
{
    public function testInvokeWithExistingProduct(): void
    {
        $product = new Product();
        $product->setName('Test Product');

        $repository = $this->createMock(ProductRepository::class);
        $repository->method('find')->with(1)->willReturn($product);

        $controller = new ShowProductController($repository);

        $response = $controller(1);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInvokeWithNonExistingProduct(): void
    {
        $repository = $this->createMock(ProductRepository::class);
        $repository->method('find')->with(999)->willReturn(null);

        $controller = new ShowProductController($repository);

        $this->expectException(NotFoundHttpException::class);

        $controller(999);
    }
}
```

---

## Streaming Responses

Handle large datasets and real-time data efficiently.

### CSV Export Stream

```php
namespace App\Controller\Export;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/export/users.csv', name: 'export_users')]
class ExportUsersController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            // Write BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write header
            fputcsv($handle, [
                'ID',
                'Name',
                'Email',
                'Created At',
                'Status',
            ]);

            // Stream users in batches to avoid memory issues
            $batchSize = 100;
            $offset = 0;

            while (true) {
                $users = $this->userRepository->findBy(
                    [],
                    ['id' => 'ASC'],
                    $batchSize,
                    $offset
                );

                if (empty($users)) {
                    break;
                }

                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->getId(),
                        $user->getName(),
                        $user->getEmail(),
                        $user->getCreatedAt()->format('Y-m-d H:i:s'),
                        $user->isActive() ? 'Active' : 'Inactive',
                    ]);
                }

                $offset += $batchSize;

                // Clear entity manager to free memory
                $this->entityManager->clear();

                // Flush output buffer
                flush();
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }
}
```

### JSON Streaming for Large Datasets

```php
namespace App\Controller\Api;

use App\Repository\LogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs/stream', name: 'api_logs_stream')]
class StreamLogsController extends AbstractController
{
    public function __construct(
        private LogRepository $logRepository,
    ) {}

    public function __invoke(): StreamedResponse
    {
        $response = new StreamedResponse(function () {
            echo '{"logs":[';

            $first = true;
            $batchSize = 50;
            $offset = 0;

            while (true) {
                $logs = $this->logRepository->findBy(
                    [],
                    ['created_at' => 'DESC'],
                    $batchSize,
                    $offset
                );

                if (empty($logs)) {
                    break;
                }

                foreach ($logs as $log) {
                    if (!$first) {
                        echo ',';
                    }

                    echo json_encode([
                        'id' => $log->getId(),
                        'level' => $log->getLevel(),
                        'message' => $log->getMessage(),
                        'created_at' => $log->getCreatedAt()->format('c'),
                    ]);

                    $first = false;
                }

                $offset += $batchSize;

                flush();
            }

            echo ']}';
        });

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Accel-Buffering', 'no'); // Disable nginx buffering

        return $response;
    }
}
```

### Server-Sent Events (SSE)

```php
namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/notifications/stream', name: 'notifications_stream')]
class NotificationStreamController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function __invoke(#[CurrentUser] User $user): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($user) {
            // Keep connection alive
            set_time_limit(0);

            $lastEventId = 0;

            while (true) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }

                // Get new notifications
                $notifications = $this->notificationService
                    ->getNewNotifications($user, $lastEventId);

                foreach ($notifications as $notification) {
                    // Send SSE event
                    echo "id: {$notification->getId()}\n";
                    echo "event: notification\n";
                    echo "data: " . json_encode([
                        'id' => $notification->getId(),
                        'type' => $notification->getType(),
                        'message' => $notification->getMessage(),
                        'created_at' => $notification->getCreatedAt()->format('c'),
                    ]) . "\n\n";

                    $lastEventId = $notification->getId();

                    ob_flush();
                    flush();
                }

                // Send heartbeat every 30 seconds
                echo "event: heartbeat\n";
                echo "data: " . json_encode(['time' => time()]) . "\n\n";

                ob_flush();
                flush();

                // Wait before next check
                sleep(5);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Connection', 'keep-alive');

        return $response;
    }
}

// JavaScript client:
/*
const eventSource = new EventSource('/notifications/stream');

eventSource.addEventListener('notification', (e) => {
    const notification = JSON.parse(e.data);
    console.log('New notification:', notification);
    displayNotification(notification);
});

eventSource.addEventListener('heartbeat', (e) => {
    console.log('Heartbeat:', e.data);
});

eventSource.onerror = (error) => {
    console.error('SSE error:', error);
    eventSource.close();
};
*/
```

### Progress Tracking Stream

```php
namespace App\Controller\Import;

use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/import/progress/{jobId}', name: 'import_progress')]
class ImportProgressController extends AbstractController
{
    public function __construct(
        private ImportService $importService,
    ) {}

    public function __invoke(string $jobId): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($jobId) {
            set_time_limit(0);

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $status = $this->importService->getJobStatus($jobId);

                echo "data: " . json_encode([
                    'progress' => $status['progress'],
                    'total' => $status['total'],
                    'current' => $status['current'],
                    'status' => $status['status'],
                    'message' => $status['message'],
                    'errors' => $status['errors'],
                ]) . "\n\n";

                ob_flush();
                flush();

                // Stop if job is complete or failed
                if (in_array($status['status'], ['completed', 'failed'])) {
                    echo "event: close\n";
                    echo "data: Job {$status['status']}\n\n";
                    break;
                }

                sleep(1);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
```

### Chunked File Download

```php
namespace App\Controller\Download;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/download/large/{filename}', name: 'download_large_file')]
class DownloadLargeFileController extends AbstractController
{
    public function __construct(
        private string $largeFilesDirectory,
    ) {}

    public function __invoke(string $filename): StreamedResponse
    {
        $filePath = $this->largeFilesDirectory . '/' . $filename;

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found');
        }

        $fileSize = filesize($filePath);
        $chunkSize = 1024 * 1024; // 1MB chunks

        $response = new StreamedResponse(function () use ($filePath, $chunkSize) {
            $handle = fopen($filePath, 'rb');

            while (!feof($handle)) {
                echo fread($handle, $chunkSize);
                ob_flush();
                flush();
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Content-Length', (string) $fileSize);
        $response->headers->set('Accept-Ranges', 'bytes');

        return $response;
    }
}
```

---

## Content Negotiation in Controllers

Handle multiple response formats based on client preferences.

### Basic Content Negotiation

```php
namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/products', name: 'products_list')]
class ProductListController extends AbstractController
{
    public function __construct(
        private ProductRepository $repository,
        private SerializerInterface $serializer,
    ) {}

    public function __invoke(Request $request): Response
    {
        $products = $this->repository->findAll();

        // Get preferred format from Accept header
        $format = $request->getPreferredFormat(['json', 'xml', 'html']);

        return match($format) {
            'json' => $this->jsonResponse($products),
            'xml' => $this->xmlResponse($products),
            default => $this->htmlResponse($products),
        };
    }

    private function jsonResponse(array $products): Response
    {
        return $this->json($products, 200, [], [
            'groups' => ['product:read'],
        ]);
    }

    private function xmlResponse(array $products): Response
    {
        $xml = $this->serializer->serialize($products, 'xml', [
            'groups' => ['product:read'],
        ]);

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function htmlResponse(array $products): Response
    {
        return $this->render('product/list.html.twig', [
            'products' => $products,
        ]);
    }
}
```

### Format-Based Routing

```php
// Using _format routing parameter
#[Route('/products.{_format}', name: 'products_list', requirements: ['_format' => 'html|json|xml'])]
class ProductListController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $products = $this->repository->findAll();
        $format = $request->getRequestFormat();

        return match($format) {
            'json' => $this->json($products),
            'xml' => $this->xmlResponse($products),
            default => $this->render('product/list.html.twig', [
                'products' => $products,
            ]),
        };
    }
}

// URLs:
// /products.html - HTML response
// /products.json - JSON response
// /products.xml  - XML response
```

### API Versioning with Content Negotiation

```php
namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products')]
class ApiProductsController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Get version from Accept header: application/vnd.api.v2+json
        $accept = $request->headers->get('Accept', 'application/json');

        $version = '1';
        if (preg_match('/application\/vnd\.api\.v(\d+)\+json/', $accept, $matches)) {
            $version = $matches[1];
        }

        // Or from custom header
        $version = $request->headers->get('X-API-Version', '1');

        $products = $this->repository->findAll();

        return match($version) {
            '2' => $this->formatV2($products),
            '3' => $this->formatV3($products),
            default => $this->formatV1($products),
        };
    }

    private function formatV1(array $products): JsonResponse
    {
        return $this->json([
            'products' => $products,
        ], 200, [], ['groups' => ['v1:read']]);
    }

    private function formatV2(array $products): JsonResponse
    {
        return $this->json([
            'data' => $products,
            'version' => '2.0',
        ], 200, [], ['groups' => ['v2:read']]);
    }

    private function formatV3(array $products): JsonResponse
    {
        return $this->json([
            'data' => $products,
            'meta' => [
                'version' => '3.0',
                'count' => count($products),
            ],
        ], 200, [], ['groups' => ['v3:read']]);
    }
}
```

### Language Negotiation

```php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/welcome', name: 'welcome')]
class WelcomeController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        // Get preferred language from Accept-Language header
        $locale = $request->getPreferredLanguage(['en', 'fr', 'de', 'es']);

        // Or use query parameter override
        if ($request->query->has('lang')) {
            $locale = $request->query->get('lang');
        }

        $request->setLocale($locale);

        return $this->render('welcome.html.twig', [
            'locale' => $locale,
        ]);
    }
}
```

### Comprehensive Content Negotiation

```php
namespace App\Controller;

use App\Service\ResponseFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/resources', name: 'api_resources')]
class ApiResourceController extends AbstractController
{
    private const SUPPORTED_FORMATS = ['json', 'xml', 'csv', 'yaml'];
    private const SUPPORTED_VERSIONS = ['1', '2', '3'];

    public function __construct(
        private ResponseFormatter $formatter,
    ) {}

    public function __invoke(Request $request): Response
    {
        // 1. Determine format
        $format = $this->negotiateFormat($request);

        // 2. Determine version
        $version = $this->negotiateVersion($request);

        // 3. Determine language
        $locale = $this->negotiateLanguage($request);

        // 4. Get data
        $data = $this->getData($locale);

        // 5. Format response
        return $this->formatter->format($data, $format, $version);
    }

    private function negotiateFormat(Request $request): string
    {
        // 1. Check URL parameter
        if ($format = $request->query->get('format')) {
            if (in_array($format, self::SUPPORTED_FORMATS)) {
                return $format;
            }
        }

        // 2. Check Accept header
        $format = $request->getPreferredFormat(self::SUPPORTED_FORMATS);

        if (!$format || !in_array($format, self::SUPPORTED_FORMATS)) {
            throw new NotAcceptableHttpException(
                'Supported formats: ' . implode(', ', self::SUPPORTED_FORMATS)
            );
        }

        return $format;
    }

    private function negotiateVersion(Request $request): string
    {
        // 1. Check custom header
        if ($version = $request->headers->get('X-API-Version')) {
            if (in_array($version, self::SUPPORTED_VERSIONS)) {
                return $version;
            }
        }

        // 2. Check Accept header (vendor MIME type)
        $accept = $request->headers->get('Accept', '');
        if (preg_match('/application\/vnd\.api\.v(\d+)/', $accept, $matches)) {
            $version = $matches[1];
            if (in_array($version, self::SUPPORTED_VERSIONS)) {
                return $version;
            }
        }

        // 3. Default to latest
        return end(self::SUPPORTED_VERSIONS);
    }

    private function negotiateLanguage(Request $request): string
    {
        return $request->getPreferredLanguage(['en', 'fr', 'de', 'es']) ?? 'en';
    }

    private function getData(string $locale): array
    {
        // Fetch localized data
        return [];
    }
}
```

---

## Summary

This deep dive covered advanced controller techniques:

### Custom Argument Value Resolvers
- Create specialized resolvers for complex argument types
- Use attributes for configuration
- Implement validation and transformation logic
- Build composite resolvers for complete request context

### Controller as a Service
- Explicit dependency injection via constructor
- Better testability and type safety
- Clear dependency requirements
- Service locator pattern for optional dependencies

### Invokable Controllers
- Single-action controllers using `__invoke()`
- Better organization and single responsibility
- Clearer routing and dependencies
- Easier testing and maintenance

### Streaming Responses
- Handle large datasets efficiently
- Implement CSV/JSON streaming
- Server-Sent Events for real-time updates
- Progress tracking and chunked downloads

### Content Negotiation
- Support multiple response formats (JSON, XML, HTML)
- API versioning strategies
- Language negotiation
- Format-based routing

These advanced patterns help you build more maintainable, scalable, and sophisticated Symfony applications!
