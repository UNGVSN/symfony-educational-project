# Framework Examples

This document shows practical examples of using the framework.

## Basic Examples

### 1. Simple Controller

```php
// src/Controller/WelcomeController.php
<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;

class WelcomeController
{
    public function index(): Response
    {
        return new Response('<h1>Welcome!</h1>');
    }
}
```

### 2. Controller with Route Parameters

```php
public function show(int $id): Response
{
    return new Response("Showing item {$id}");
}

// Route: /items/{id} with requirement: id => '\d+'
```

### 3. Controller with Request

```php
use Symfony\Component\HttpFoundation\Request;

public function create(Request $request): Response
{
    $name = $request->request->get('name');
    $email = $request->request->get('email');
    
    // Process form data...
    
    return new Response('Form submitted!');
}
```

### 4. Controller with Dependency Injection

```php
use App\Repository\PostRepository;
use Twig\Environment;

class BlogController
{
    public function __construct(
        private Environment $twig,
        private PostRepository $repository
    ) {}

    public function index(): Response
    {
        $posts = $this->repository->findAll();
        
        $html = $this->twig->render('blog/index.html.twig', [
            'posts' => $posts
        ]);
        
        return new Response($html);
    }
}
```

## Advanced Examples

### 1. JSON API Endpoint

```php
use Symfony\Component\HttpFoundation\JsonResponse;

public function apiPosts(): JsonResponse
{
    $posts = $this->repository->findAll();
    
    $data = array_map(function($post) {
        return [
            'id' => $post->getId(),
            'title' => $post->getTitle(),
            'slug' => $post->getSlug(),
        ];
    }, $posts);
    
    return new JsonResponse($data);
}
```

### 2. File Upload

```php
public function upload(Request $request): Response
{
    $file = $request->files->get('document');
    
    if ($file) {
        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move(__DIR__ . '/../../var/uploads', $filename);
        
        return new Response('File uploaded: ' . $filename);
    }
    
    return new Response('No file uploaded', 400);
}
```

### 3. Redirects

```php
use Symfony\Component\HttpFoundation\RedirectResponse;

public function oldPage(): RedirectResponse
{
    return new RedirectResponse('/new-page', 301);
}
```

### 4. Custom Headers

```php
public function download(): Response
{
    $content = file_get_contents(__DIR__ . '/../../var/files/document.pdf');
    
    return new Response($content, 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="document.pdf"'
    ]);
}
```

### 5. Session Management

```php
use Symfony\Component\HttpFoundation\Session\Session;

public function login(Request $request): Response
{
    $session = $request->getSession();
    $session->set('user_id', 123);
    $session->set('username', 'john');
    
    return new Response('Logged in!');
}

public function profile(Request $request): Response
{
    $session = $request->getSession();
    $userId = $session->get('user_id');
    
    if (!$userId) {
        return new RedirectResponse('/login');
    }
    
    return new Response("Welcome, user {$userId}");
}
```

### 6. Custom Event Listener

```php
// src/EventListener/RequestLogger.php
<?php

namespace App\EventListener;

class RequestLogger
{
    public function onKernelRequest($event): void
    {
        $request = $event->getRequest();
        
        $logMessage = sprintf(
            "%s %s from %s\n",
            $request->getMethod(),
            $request->getPathInfo(),
            $request->getClientIp()
        );
        
        file_put_contents(
            __DIR__ . '/../../var/log/requests.log',
            $logMessage,
            FILE_APPEND
        );
    }
}

// Register in Kernel
$dispatcher->addListener('kernel.request', [
    new RequestLogger(),
    'onKernelRequest'
]);
```

### 7. Response Modifier Listener

```php
// src/EventListener/ResponseModifier.php
class ResponseModifier
{
    public function onKernelResponse($event): void
    {
        $response = $event->getResponse();
        
        // Add custom header to all responses
        $response->headers->set('X-Framework', 'Custom PHP Framework');
        
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
    }
}

// Register
$dispatcher->addListener('kernel.response', [
    new ResponseModifier(),
    'onKernelResponse'
]);
```

### 8. Custom Service with Configuration

```php
// src/Service/Mailer.php
class Mailer
{
    public function __construct(
        private string $fromEmail,
        private string $fromName
    ) {}
    
    public function send(string $to, string $subject, string $body): void
    {
        // Send email logic...
        mail($to, $subject, $body, "From: {$this->fromName} <{$this->fromEmail}>");
    }
}

// config/services.php
return function (ContainerBuilder $container) {
    $container->autowire(Mailer::class)
        ->setArgument('$fromEmail', 'noreply@example.com')
        ->setArgument('$fromName', 'My App');
};

// Use in controller
public function __construct(private Mailer $mailer) {}

public function contact(Request $request): Response
{
    $this->mailer->send(
        'admin@example.com',
        'New contact form',
        $request->request->get('message')
    );
    
    return new Response('Message sent!');
}
```

### 9. Database Integration (Example with PDO)

```php
// src/Service/Database.php
class Database
{
    private PDO $pdo;
    
    public function __construct(string $dsn, string $user, string $pass)
    {
        $this->pdo = new PDO($dsn, $user, $pass);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// config/services.php
$container->set(Database::class, new Database(
    'mysql:host=localhost;dbname=myapp',
    'username',
    'password'
));

// Use in repository
class UserRepository
{
    public function __construct(private Database $db) {}
    
    public function find(int $id): ?array
    {
        $results = $this->db->query(
            'SELECT * FROM users WHERE id = ?',
            [$id]
        );
        
        return $results[0] ?? null;
    }
}
```

### 10. Form Validation Example

```php
// src/Validator/ContactFormValidator.php
class ContactFormValidator
{
    public function validate(array $data): array
    {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address';
        }
        
        if (empty($data['message']) || strlen($data['message']) < 10) {
            $errors['message'] = 'Message must be at least 10 characters';
        }
        
        return $errors;
    }
}

// In controller
public function submitContact(Request $request, ContactFormValidator $validator): Response
{
    $data = $request->request->all();
    $errors = $validator->validate($data);
    
    if (!empty($errors)) {
        return new Response(
            $this->twig->render('contact/form.html.twig', [
                'errors' => $errors,
                'data' => $data
            ]),
            400
        );
    }
    
    // Process valid form...
    return new Response('Thank you for your message!');
}
```

### 11. Middleware-Style Request Modification

```php
// src/EventListener/AuthenticationListener.php
class AuthenticationListener
{
    public function onKernelRequest($event): void
    {
        $request = $event->getRequest();
        
        // Check for API token
        $token = $request->headers->get('X-API-Token');
        
        if ($request->getPathInfo() === '/api' && !$token) {
            $response = new JsonResponse([
                'error' => 'Authentication required'
            ], 401);
            
            $event->setResponse($response);
            // Request handling stops here
        }
    }
}
```

### 12. Caching Example

```php
// src/Service/Cache.php
class Cache
{
    private string $cacheDir;
    
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }
    
    public function get(string $key): mixed
    {
        $file = $this->cacheDir . '/' . md5($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $file = $this->cacheDir . '/' . md5($key);
        
        file_put_contents($file, serialize([
            'value' => $value,
            'expires' => time() + $ttl
        ]));
    }
}

// Use in controller
public function expensiveOperation(Cache $cache): Response
{
    $result = $cache->get('expensive_result');
    
    if ($result === null) {
        // Perform expensive operation
        $result = $this->performExpensiveOperation();
        $cache->set('expensive_result', $result, 3600);
    }
    
    return new Response($result);
}
```

## Testing Examples

### 1. Controller Test

```php
// tests/Controller/BlogControllerTest.php
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BlogControllerTest extends TestCase
{
    public function testIndex(): void
    {
        $twig = $this->createMock(Environment::class);
        $repository = $this->createMock(PostRepository::class);
        
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        
        $controller = new BlogController($twig, $repository);
        $response = $controller->index(new Request());
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### 2. Integration Test

```php
public function testCompleteFlow(): void
{
    $kernel = new Kernel('test', true);
    $request = Request::create('/blog/1');
    $response = $kernel->handle($request);
    
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertStringContainsString('Blog Post', $response->getContent());
}
```

## Best Practices

### 1. Keep Controllers Thin

```php
// Bad
public function index(): Response
{
    // 100 lines of business logic...
}

// Good
public function index(PostService $postService): Response
{
    $posts = $postService->getPublishedPosts();
    return $this->render('blog/index.html.twig', ['posts' => $posts]);
}
```

### 2. Use Type Hints

```php
// Good - type safety and autocomplete
public function show(
    int $id,
    PostRepository $repository,
    Request $request
): Response
```

### 3. Dependency Injection over Static Calls

```php
// Bad
Database::query('SELECT ...');

// Good
public function __construct(private Database $db) {}
$this->db->query('SELECT ...');
```

### 4. Return Early

```php
public function show(int $id): Response
{
    $post = $this->repository->find($id);
    
    if (!$post) {
        return new Response('Not found', 404);
    }
    
    // Continue with normal flow...
}
```

### 5. Use Services for Business Logic

```php
// src/Service/PostService.php
class PostService
{
    public function __construct(
        private PostRepository $repository,
        private Mailer $mailer
    ) {}
    
    public function publishPost(Post $post): void
    {
        $post->setPublished(true);
        $this->repository->save($post);
        $this->mailer->notifySubscribers($post);
    }
}
```

## Common Patterns

### Repository Pattern
- Separate data access from business logic
- Encapsulate queries
- Easy to test with mocks

### Service Layer
- Business logic in services
- Reusable across controllers
- Testable in isolation

### Event Listeners
- Cross-cutting concerns (logging, caching)
- Loose coupling
- Easy to add/remove

### Dependency Injection
- Don't create, inject
- Better testability
- Flexibility

Happy coding!
