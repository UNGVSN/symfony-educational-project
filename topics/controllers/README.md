# Controllers

Master Symfony controllers for handling HTTP requests and generating responses.

---

## Learning Objectives

After completing this topic, you will be able to:

- Create controllers following Symfony conventions
- Handle requests and generate various response types
- Work with sessions, cookies, and flash messages
- Implement file uploads and downloads
- Use argument resolvers and parameter converters
- Apply security at the controller level

---

## Prerequisites

- Symfony Architecture basics
- HTTP protocol understanding
- Routing fundamentals

---

## Topics Covered

1. [Controller Basics](#1-controller-basics)
2. [Request Handling](#2-request-handling)
3. [Response Types](#3-response-types)
4. [Sessions and Cookies](#4-sessions-and-cookies)
5. [Flash Messages](#5-flash-messages)
6. [File Handling](#6-file-handling)
7. [Argument Resolvers](#7-argument-resolvers)
8. [Controller Security](#8-controller-security)

---

## 1. Controller Basics

### Controller Conventions

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogController extends AbstractController
{
    #[Route('/blog', name: 'blog_index')]
    public function index(): Response
    {
        return $this->render('blog/index.html.twig', [
            'posts' => $this->getPosts(),
        ]);
    }

    #[Route('/blog/{slug}', name: 'blog_show')]
    public function show(string $slug): Response
    {
        // $slug is automatically injected from the URL
        return $this->render('blog/show.html.twig', [
            'slug' => $slug,
        ]);
    }
}
```

### AbstractController Helper Methods

```php
class ExampleController extends AbstractController
{
    public function helpers(): void
    {
        // Render template
        $this->render('template.html.twig', ['var' => 'value']);

        // Render with custom response
        $this->render('template.html.twig', [], new Response('', 200, ['X-Custom' => 'header']));

        // JSON response
        $this->json(['key' => 'value']);

        // Redirect to URL
        $this->redirect('https://example.com');

        // Redirect to route
        $this->redirectToRoute('route_name', ['param' => 'value']);

        // Generate URL
        $this->generateUrl('route_name', ['param' => 'value']);

        // Add flash message
        $this->addFlash('success', 'Operation completed!');

        // Get user
        $this->getUser();

        // Check permission
        $this->isGranted('ROLE_ADMIN');

        // Deny access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Create form
        $this->createForm(PostType::class, $post);

        // Get parameter
        $this->getParameter('app.admin_email');

        // File response
        $this->file('/path/to/file.pdf');
    }
}
```

### Controllers as Services

```php
// Controller with injected dependencies
class PostController extends AbstractController
{
    public function __construct(
        private PostRepository $postRepository,
        private LoggerInterface $logger,
    ) {}

    #[Route('/posts', name: 'post_index')]
    public function index(): Response
    {
        $posts = $this->postRepository->findAllPublished();

        $this->logger->info('Posts retrieved', ['count' => count($posts)]);

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }
}
```

---

## 2. Request Handling

### Accessing Request Data

```php
use Symfony\Component\HttpFoundation\Request;

#[Route('/search', name: 'search')]
public function search(Request $request): Response
{
    // Query parameters (?q=value)
    $query = $request->query->get('q');
    $page = $request->query->getInt('page', 1);

    // POST data
    $username = $request->request->get('username');

    // Get from both (query first, then request)
    $value = $request->get('key');

    // Files
    $uploadedFile = $request->files->get('document');

    // Headers
    $contentType = $request->headers->get('Content-Type');
    $authHeader = $request->headers->get('Authorization');

    // Cookies
    $sessionId = $request->cookies->get('PHPSESSID');

    // Server variables
    $host = $request->server->get('HTTP_HOST');

    // Request metadata
    $method = $request->getMethod();         // GET, POST, etc.
    $path = $request->getPathInfo();         // /search
    $uri = $request->getRequestUri();        // /search?q=value
    $baseUrl = $request->getBaseUrl();
    $clientIp = $request->getClientIp();
    $locale = $request->getLocale();
    $format = $request->getRequestFormat();  // html, json, etc.

    // Check request type
    $request->isMethod('POST');
    $request->isXmlHttpRequest();  // AJAX
    $request->isSecure();          // HTTPS

    // Content
    $rawContent = $request->getContent();    // Raw body
    $jsonData = $request->toArray();         // JSON decoded (PHP 8.0+)

    return $this->json(['query' => $query]);
}
```

### Request Attributes

```php
#[Route('/post/{id}', name: 'post_show')]
public function show(Request $request): Response
{
    // Route parameters are stored in attributes
    $id = $request->attributes->get('id');
    $routeName = $request->attributes->get('_route');
    $routeParams = $request->attributes->get('_route_params');

    return $this->render('post/show.html.twig');
}
```

### Content Negotiation

```php
#[Route('/api/posts', name: 'api_posts')]
public function posts(Request $request): Response
{
    $posts = $this->postRepository->findAll();

    // Check accepted formats
    $format = $request->getPreferredFormat(['json', 'xml', 'html']);

    return match($format) {
        'json' => $this->json($posts),
        'xml' => new Response($this->serializeXml($posts), 200, [
            'Content-Type' => 'application/xml',
        ]),
        default => $this->render('posts/index.html.twig', ['posts' => $posts]),
    };
}
```

---

## 3. Response Types

### Basic Response

```php
use Symfony\Component\HttpFoundation\Response;

#[Route('/hello')]
public function hello(): Response
{
    // Basic response
    return new Response('Hello World!');

    // With status and headers
    return new Response(
        'Not Found',
        Response::HTTP_NOT_FOUND,
        ['Content-Type' => 'text/plain']
    );

    // Modify response
    $response = new Response();
    $response->setContent('Content');
    $response->setStatusCode(200);
    $response->headers->set('Content-Type', 'text/html');
    $response->headers->setCookie(Cookie::create('name', 'value'));

    return $response;
}
```

### JSON Response

```php
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/api/users')]
public function users(): JsonResponse
{
    // Using helper
    return $this->json([
        'users' => $users,
        'count' => count($users),
    ]);

    // With status and headers
    return $this->json(
        ['error' => 'Not found'],
        Response::HTTP_NOT_FOUND,
        ['X-Custom-Header' => 'value']
    );

    // Direct instantiation
    return new JsonResponse(['key' => 'value']);

    // From string
    return JsonResponse::fromJsonString('{"key": "value"}');
}
```

### Redirect Response

```php
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/old-page')]
public function oldPage(): RedirectResponse
{
    // Redirect to route
    return $this->redirectToRoute('new_page', ['id' => 1]);

    // With status code (301 permanent)
    return $this->redirectToRoute('new_page', [], 301);

    // Redirect to URL
    return $this->redirect('https://example.com');

    // Direct instantiation
    return new RedirectResponse('/path', 302);
}
```

### File Response

```php
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

#[Route('/download/{filename}')]
public function download(string $filename): BinaryFileResponse
{
    $path = $this->getParameter('files_directory') . '/' . $filename;

    // Using helper
    return $this->file($path);

    // With custom filename
    return $this->file($path, 'custom-name.pdf');

    // Force download
    return $this->file($path, 'document.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

    // Direct instantiation with options
    $response = new BinaryFileResponse($path);
    $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        'download.pdf'
    );
    $response->deleteFileAfterSend(true);

    return $response;
}
```

### Streamed Response

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Route('/export')]
public function export(): StreamedResponse
{
    return new StreamedResponse(function () {
        $handle = fopen('php://output', 'w');

        // Headers
        fputcsv($handle, ['ID', 'Name', 'Email']);

        // Stream data
        foreach ($this->userRepository->findAll() as $user) {
            fputcsv($handle, [
                $user->getId(),
                $user->getName(),
                $user->getEmail(),
            ]);
        }

        fclose($handle);
    }, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="users.csv"',
    ]);
}
```

---

## 4. Sessions and Cookies

### Session Handling

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartController extends AbstractController
{
    // Option 1: Inject via Request
    #[Route('/cart/add')]
    public function add(Request $request): Response
    {
        $session = $request->getSession();

        $cart = $session->get('cart', []);
        $cart[] = $productId;
        $session->set('cart', $cart);

        return $this->redirectToRoute('cart_show');
    }

    // Option 2: Inject SessionInterface
    #[Route('/cart')]
    public function show(SessionInterface $session): Response
    {
        $cart = $session->get('cart', []);

        // Session methods
        $session->set('key', 'value');
        $session->get('key', 'default');
        $session->has('key');
        $session->remove('key');
        $session->clear();

        // Session metadata
        $session->getId();
        $session->getName();
        $session->isStarted();

        // Regenerate ID (after login)
        $session->migrate();

        // Invalidate session
        $session->invalidate();

        return $this->render('cart/show.html.twig', ['cart' => $cart]);
    }
}
```

### Cookie Handling

```php
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

#[Route('/preferences')]
public function setPreferences(Request $request): Response
{
    $theme = $request->query->get('theme', 'light');

    $response = $this->redirectToRoute('homepage');

    // Create cookie
    $cookie = Cookie::create('theme')
        ->withValue($theme)
        ->withExpires(new \DateTime('+1 year'))
        ->withPath('/')
        ->withSecure(true)
        ->withHttpOnly(true)
        ->withSameSite('lax');

    $response->headers->setCookie($cookie);

    // Alternative: Quick cookie
    $response->headers->setCookie(
        Cookie::create('quick', 'value', strtotime('+1 day'))
    );

    // Remove cookie
    $response->headers->clearCookie('old_cookie');

    return $response;
}

#[Route('/get-preferences')]
public function getPreferences(Request $request): Response
{
    $theme = $request->cookies->get('theme', 'light');

    return $this->json(['theme' => $theme]);
}
```

---

## 5. Flash Messages

### Setting Flash Messages

```php
#[Route('/post/create', methods: ['POST'])]
public function create(Request $request): Response
{
    try {
        // Create post...

        $this->addFlash('success', 'Post created successfully!');
        $this->addFlash('info', 'You can now edit your post.');

        return $this->redirectToRoute('post_show', ['id' => $post->getId()]);

    } catch (\Exception $e) {
        $this->addFlash('error', 'Failed to create post: ' . $e->getMessage());

        return $this->redirectToRoute('post_new');
    }
}
```

### Displaying Flash Messages (Twig)

```twig
{# templates/base.html.twig #}

{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type }}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}

{# Or specific types #}
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

---

## 6. File Handling

### File Upload

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadController extends AbstractController
{
    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(
        Request $request,
        SluggerInterface $slugger,
    ): Response {
        /** @var UploadedFile $file */
        $file = $request->files->get('document');

        if (!$file) {
            throw new \InvalidArgumentException('No file uploaded');
        }

        // Validate file
        if (!$file->isValid()) {
            throw new FileException($file->getErrorMessage());
        }

        // File information
        $originalName = $file->getClientOriginalName();
        $extension = $file->guessExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate safe filename
        $safeFilename = $slugger->slug(pathinfo($originalName, PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Move file
        try {
            $file->move(
                $this->getParameter('uploads_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file');
        }

        $this->addFlash('success', 'File uploaded: ' . $newFilename);

        return $this->redirectToRoute('homepage');
    }
}
```

### File Validation with Constraints

```php
// In Form Type
use Symfony\Component\Validator\Constraints\File;

$builder->add('document', FileType::class, [
    'constraints' => [
        new File([
            'maxSize' => '5M',
            'mimeTypes' => [
                'application/pdf',
                'application/x-pdf',
            ],
            'mimeTypesMessage' => 'Please upload a valid PDF document',
        ]),
    ],
]);
```

---

## 7. Argument Resolvers

### Built-in Argument Resolvers

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/example/{id}')]
public function example(
    // Route parameters
    int $id,

    // Request object
    Request $request,

    // Session
    SessionInterface $session,

    // Current user
    ?UserInterface $user,

    // Entity via ParamConverter
    Post $post,  // Automatically fetched by $id

    // Service injection
    LoggerInterface $logger,
): Response {
    // All arguments automatically resolved
}
```

### Entity Value Resolver (ParamConverter)

```php
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

// Basic usage - finds by {id}
#[Route('/post/{id}')]
public function show(Post $post): Response
{
    return $this->render('post/show.html.twig', ['post' => $post]);
}

// Custom mapping
#[Route('/post/{slug}')]
public function showBySlug(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    Post $post
): Response {
    return $this->render('post/show.html.twig', ['post' => $post]);
}

// Multiple entities
#[Route('/post/{post_id}/comment/{comment_id}')]
public function comment(
    #[MapEntity(id: 'post_id')] Post $post,
    #[MapEntity(id: 'comment_id')] Comment $comment,
): Response {
    // ...
}

// Optional entity
#[Route('/category/{slug?}')]
public function list(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    ?Category $category = null
): Response {
    // $category is null if not found
}

// Custom expression
#[Route('/post/{slug}')]
public function showExpr(
    #[MapEntity(expr: 'repository.findBySlugAndActive(slug)')]
    Post $post
): Response {
    // ...
}
```

### Custom Argument Resolver

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CurrentUserResolver implements ValueResolverInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only resolve for User type with #[CurrentUser] attribute
        if ($argument->getType() !== User::class) {
            return [];
        }

        $attributes = $argument->getAttributes(CurrentUser::class, ArgumentMetadata::IS_INSTANCEOF);
        if (empty($attributes)) {
            return [];
        }

        $user = $this->security->getUser();

        if (!$user instanceof User && !$argument->isNullable()) {
            throw new AccessDeniedException();
        }

        yield $user;
    }
}

// Usage
#[Route('/profile')]
public function profile(#[CurrentUser] User $user): Response
{
    return $this->render('profile/show.html.twig', ['user' => $user]);
}
```

---

## 8. Controller Security

### Access Control Attributes

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminController extends AbstractController
{
    // Require role for entire controller
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    // Require permission on entity
    #[Route('/post/{id}/edit')]
    #[IsGranted('EDIT', subject: 'post')]
    public function edit(Post $post): Response
    {
        return $this->render('post/edit.html.twig', ['post' => $post]);
    }

    // Custom status code on denial
    #[IsGranted('ROLE_PREMIUM', statusCode: 403, message: 'Premium subscription required')]
    #[Route('/premium')]
    public function premium(): Response
    {
        return $this->render('premium/index.html.twig');
    }
}
```

### Manual Security Checks

```php
#[Route('/post/{id}/delete', methods: ['POST'])]
public function delete(Post $post): Response
{
    // Check if logged in
    $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

    // Check role
    if (!$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException('Admin access required');
    }

    // Check voter permission
    $this->denyAccessUnlessGranted('DELETE', $post);

    // Proceed with deletion
    $this->entityManager->remove($post);
    $this->entityManager->flush();

    return $this->redirectToRoute('post_index');
}
```

### CSRF Protection

```php
#[Route('/post/{id}/delete', methods: ['POST'])]
public function delete(Request $request, Post $post): Response
{
    // Validate CSRF token
    $token = $request->request->get('_token');

    if (!$this->isCsrfTokenValid('delete-post-' . $post->getId(), $token)) {
        throw $this->createAccessDeniedException('Invalid CSRF token');
    }

    // Safe to proceed
    $this->entityManager->remove($post);
    $this->entityManager->flush();

    return $this->redirectToRoute('post_index');
}
```

```twig
{# In template #}
<form method="post" action="{{ path('post_delete', {id: post.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete-post-' ~ post.id) }}">
    <button type="submit">Delete</button>
</form>
```

---

## Best Practices

### 1. Keep Controllers Thin

```php
// BAD: Fat controller
#[Route('/order/create', methods: ['POST'])]
public function create(Request $request): Response
{
    // Validation logic here
    // Business logic here
    // Email sending here
    // Database operations here
    // 100+ lines of code
}

// GOOD: Thin controller, delegate to services
#[Route('/order/create', methods: ['POST'])]
public function create(
    Request $request,
    OrderService $orderService,
): Response {
    $data = $request->toArray();
    $order = $orderService->createOrder($data);

    return $this->json($order, Response::HTTP_CREATED);
}
```

### 2. Use Type Hints

```php
// Always type hint parameters and return types
#[Route('/post/{id}')]
public function show(int $id, PostRepository $repository): Response
{
    $post = $repository->find($id);

    if (!$post) {
        throw $this->createNotFoundException();
    }

    return $this->render('post/show.html.twig', ['post' => $post]);
}
```

### 3. Handle Errors Gracefully

```php
#[Route('/api/posts/{id}')]
public function show(int $id): JsonResponse
{
    try {
        $post = $this->postRepository->findOrFail($id);
        return $this->json($post);
    } catch (EntityNotFoundException $e) {
        return $this->json(['error' => 'Post not found'], 404);
    } catch (\Exception $e) {
        $this->logger->error('Unexpected error', ['exception' => $e]);
        return $this->json(['error' => 'Internal error'], 500);
    }
}
```

---

## Exercises

### Exercise 1: Build a CRUD Controller
Create a complete CRUD controller for a `Product` entity with proper validation and flash messages.

### Exercise 2: File Upload System
Implement a file upload controller with validation, secure filename generation, and download functionality.

### Exercise 3: API Controller
Create a RESTful API controller with JSON responses, proper status codes, and error handling.

---

## Resources

- [Symfony Controllers](https://symfony.com/doc/current/controller.html)
- [AbstractController Reference](https://symfony.com/doc/current/controller.html#the-base-controller-class-services)
- [Request and Response](https://symfony.com/doc/current/components/http_foundation.html)
