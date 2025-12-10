# Controller Examples

This document provides detailed examples of different controller patterns and use cases.

## Table of Contents

1. [Basic Controller Formats](#basic-controller-formats)
2. [Argument Resolution Examples](#argument-resolution-examples)
3. [Response Type Examples](#response-type-examples)
4. [Advanced Patterns](#advanced-patterns)
5. [Testing Controllers](#testing-controllers)

## Basic Controller Formats

### 1. Closure Controller

The simplest form - perfect for quick actions or prototyping.

```php
// In public/index.php
$routes->add('hello', new Route('/hello', [
    '_controller' => function () {
        return new Response('Hello, World!');
    }
]));
```

**When to use:**
- Quick prototypes
- Simple endpoints
- Learning and experimentation

**Drawbacks:**
- Not reusable
- Hard to test
- No IDE support

### 2. Class Method (Array Format)

Type-safe and refactoring-friendly.

```php
// In public/index.php
$routes->add('blog_list', new Route('/blog', [
    '_controller' => [BlogController::class, 'index']
]));

// In src/Controller/BlogController.php
class BlogController extends AbstractController
{
    public function index(): Response
    {
        return $this->html('<h1>Blog List</h1>');
    }
}
```

**When to use:**
- Production code
- Reusable controllers
- When you need IDE autocomplete

**Benefits:**
- IDE-friendly
- Refactoring-safe
- Easy to test

### 3. Class Method (String Format)

Clean syntax, commonly used in Symfony.

```php
$routes->add('blog_show', new Route('/blog/{id}', [
    '_controller' => 'App\Controller\BlogController::show'
]));
```

**When to use:**
- Configuration files
- When you prefer string-based config

**Drawbacks:**
- Not refactoring-safe
- Can break silently

### 4. Invokable Controller

Single-action controller with `__invoke` method.

```php
// In public/index.php
$routes->add('contact', new Route('/contact', [
    '_controller' => ContactController::class
]));

// In src/Controller/ContactController.php
class ContactController
{
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Process form
        }
        return new Response('Contact form');
    }
}
```

**When to use:**
- Single-action controllers
- Clear, focused responsibility
- Following single responsibility principle

## Argument Resolution Examples

### Example 1: Route Parameters

Route parameters are automatically injected by name.

```php
// Route: /user/{id}
public function showUser(int $id): Response
{
    return new Response("User ID: {$id}");
}

// Route: /blog/{slug}
public function showPost(string $slug): Response
{
    return new Response("Post slug: {$slug}");
}

// Multiple parameters
// Route: /category/{category}/post/{id}
public function showCategoryPost(string $category, int $id): Response
{
    return new Response("Category: {$category}, Post: {$id}");
}
```

### Example 2: Request Injection

The Request object is automatically injected when type-hinted.

```php
public function create(Request $request): Response
{
    $title = $request->request->get('title');
    $content = $request->request->get('content');

    return $this->json([
        'title' => $title,
        'content' => $content
    ]);
}
```

### Example 3: Mixed Parameters

Combine Request injection with route parameters.

```php
// Route: /post/{id}/edit
public function edit(Request $request, int $id): Response
{
    // Request is always first when combined with route params

    if ($request->isMethod('POST')) {
        $title = $request->request->get('title');
        // Update post $id with new $title
    }

    return new Response("Edit post {$id}");
}
```

### Example 4: Optional Parameters

Use default values for optional parameters.

```php
// Route: /posts
public function listPosts(int $page = 1, int $limit = 10): Response
{
    return $this->json([
        'page' => $page,
        'limit' => $limit,
        'posts' => [] // fetch posts
    ]);
}
```

### Example 5: Nullable Parameters

Use nullable types for optional route parameters.

```php
// Route: /search
public function search(?string $query = null): Response
{
    if ($query === null) {
        return new Response('Please provide a search query');
    }

    return new Response("Searching for: {$query}");
}
```

## Response Type Examples

### HTML Response

```php
public function index(): Response
{
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Home</title></head>
<body><h1>Welcome</h1></body>
</html>
HTML;

    return $this->html($html);
}
```

### JSON Response

```php
public function apiUsers(): Response
{
    $users = [
        ['id' => 1, 'name' => 'John Doe'],
        ['id' => 2, 'name' => 'Jane Smith'],
    ];

    return $this->json($users);
}

// With custom status code
public function apiError(): Response
{
    return $this->json([
        'error' => 'Not found'
    ], 404);
}
```

### Redirect Response

```php
public function oldUrl(): Response
{
    return $this->redirect('/new-url', 301);
}

public function afterLogin(): Response
{
    return $this->redirectToRoute('/dashboard');
}
```

### Text Response

```php
public function robots(): Response
{
    $content = <<<TXT
User-agent: *
Disallow: /admin/
Allow: /
TXT;

    return $this->text($content);
}
```

### File Download

```php
public function downloadReport(): Response
{
    $file = '/path/to/report.pdf';
    return $this->file($file, 'monthly-report.pdf');
}
```

### Streamed Response

```php
public function streamData(): Response
{
    return $this->stream(function () {
        for ($i = 1; $i <= 10; $i++) {
            echo "Line {$i}\n";
            flush();
            sleep(1);
        }
    });
}
```

## Advanced Patterns

### Pattern 1: Form Handling

```php
public function contactForm(Request $request): Response
{
    if ($request->isMethod('POST')) {
        $data = [
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'message' => $request->request->get('message'),
        ];

        // Validate
        if (empty($data['name']) || empty($data['email'])) {
            return $this->badRequest('Name and email are required');
        }

        // Process (send email, save to DB, etc.)

        return $this->json([
            'status' => 'success',
            'message' => 'Form submitted successfully'
        ]);
    }

    // Show form
    return $this->render('contact_form');
}
```

### Pattern 2: RESTful API Controller

```php
class PostApiController extends AbstractController
{
    // GET /api/posts
    public function index(Request $request): Response
    {
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        // Fetch posts
        $posts = []; // from database

        return $this->json([
            'data' => $posts,
            'meta' => [
                'total' => count($posts),
                'limit' => $limit,
                'offset' => $offset,
            ]
        ]);
    }

    // GET /api/posts/{id}
    public function show(int $id): Response
    {
        // Fetch post
        $post = null; // from database

        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }

        return $this->json(['data' => $post]);
    }

    // POST /api/posts
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        // Validate and create post

        return $this->json([
            'data' => $data,
            'message' => 'Post created'
        ], 201);
    }

    // PUT /api/posts/{id}
    public function update(Request $request, int $id): Response
    {
        $data = json_decode($request->getContent(), true);

        // Validate and update post

        return $this->json([
            'data' => $data,
            'message' => 'Post updated'
        ]);
    }

    // DELETE /api/posts/{id}
    public function delete(int $id): Response
    {
        // Delete post

        return $this->json([
            'message' => 'Post deleted'
        ]);
    }
}
```

### Pattern 3: Conditional Content Type

```php
public function userProfile(Request $request, int $id): Response
{
    $user = ['id' => $id, 'name' => 'John Doe']; // from DB

    // Return JSON for API requests
    if ($request->headers->get('Accept') === 'application/json') {
        return $this->json($user);
    }

    // Return HTML for browser requests
    return $this->render('user_profile', ['user' => $user]);
}
```

### Pattern 4: Error Handling

```php
public function showPost(int $id): Response
{
    try {
        $post = $this->findPost($id);

        if (!$post) {
            return $this->notFound('Post not found');
        }

        return $this->json($post);

    } catch (\Exception $e) {
        return $this->json([
            'error' => 'Server error',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

## Testing Controllers

### Unit Testing a Controller

```php
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class BlogControllerTest extends TestCase
{
    public function testIndexReturnsResponse(): void
    {
        $controller = new BlogController();
        $response = $controller->index();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShowWithValidId(): void
    {
        $controller = new BlogController();
        $response = $controller->show(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('First Post', $response->getContent());
    }

    public function testShowWithInvalidId(): void
    {
        $controller = new BlogController();
        $response = $controller->show(999);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
```

### Integration Testing

```php
class FrameworkIntegrationTest extends TestCase
{
    private Framework $framework;

    protected function setUp(): void
    {
        $routes = new RouteCollection();
        $routes->add('blog_show', new Route('/blog/{id}', [
            '_controller' => [BlogController::class, 'show']
        ]));

        $this->framework = new Framework($routes);
    }

    public function testFullRequestCycle(): void
    {
        $request = Request::create('/blog/1');
        $response = $this->framework->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

## Best Practices

1. **Return Response objects**: Always return a Response object from controllers
2. **Type-hint parameters**: Use type hints for automatic casting and validation
3. **Keep controllers thin**: Move business logic to services
4. **Use helper methods**: Leverage AbstractController helpers
5. **Handle errors gracefully**: Use try-catch and return appropriate status codes
6. **Validate input**: Always validate user input before processing
7. **Use meaningful names**: Name controllers and methods clearly
8. **Follow RESTful conventions**: For API controllers, use standard HTTP methods
9. **Test your controllers**: Write unit and integration tests
10. **Document complex logic**: Add docblocks for complex controllers

## Common Pitfalls

1. **Forgetting to return Response**: Controllers must return Response objects
2. **Parameter order matters**: Request must come first before route params
3. **Type mismatches**: Route params are strings by default, use type hints
4. **Not handling errors**: Always handle potential exceptions
5. **Business logic in controllers**: Keep controllers thin
6. **Hardcoding URLs**: Use URL generation instead
7. **Not validating input**: Always validate user input

## Next Steps

- Explore the test files in `/tests` for more examples
- Try modifying the controllers in `/src/Controller`
- Add your own routes in `/public/index.php`
- Read the main README.md for conceptual understanding
