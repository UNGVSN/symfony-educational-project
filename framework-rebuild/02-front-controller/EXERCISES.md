# Exercises - Front Controller

These exercises will help you understand and extend the Front Controller pattern.

## Exercise 1: Add a New Route

**Goal:** Add a new page to the framework.

**Task:** Add a `/services` page that lists services offered.

**Steps:**

1. Open `src/Framework.php`
2. Add a new route in the `handle()` method:
   ```php
   if ($uri === '/services' && $method === 'GET') {
       return $this->servicesAction($request);
   }
   ```
3. Add the action method:
   ```php
   private function servicesAction(Request $request): Response
   {
       $html = <<<HTML
   <!DOCTYPE html>
   <html>
   <head><title>Our Services</title></head>
   <body>
       <h1>Our Services</h1>
       <ul>
           <li>Web Development</li>
           <li>Mobile Apps</li>
           <li>Consulting</li>
       </ul>
   </body>
   </html>
   HTML;
       return new Response($html);
   }
   ```
4. Test: Visit http://localhost:8000/services

**Expected Result:** Your new page displays correctly!

## Exercise 2: Dynamic Route with Multiple Parameters

**Goal:** Create a route with multiple dynamic parameters.

**Task:** Add a route `/blog/{year}/{month}/{slug}` for blog posts.

**Steps:**

1. Add pattern matching in `handle()`:
   ```php
   if (preg_match('#^/blog/(\d{4})/(\d{2})/([a-z0-9-]+)$#', $uri, $matches)) {
       return $this->blogPostAction($request, $matches[1], $matches[2], $matches[3]);
   }
   ```

2. Add the action method:
   ```php
   private function blogPostAction(Request $request, string $year, string $month, string $slug): Response
   {
       $html = <<<HTML
   <!DOCTYPE html>
   <html>
   <body>
       <h1>Blog Post</h1>
       <p>Year: $year</p>
       <p>Month: $month</p>
       <p>Slug: $slug</p>
   </body>
   </html>
   HTML;
       return new Response($html);
   }
   ```

3. Test: http://localhost:8000/blog/2024/03/my-first-post

**Challenge:** Extract date validation - return 404 if month > 12 or year < 2000.

## Exercise 3: Handle Query Parameters

**Goal:** Use query string parameters in your action.

**Task:** Modify the products list to support sorting and filtering.

**Steps:**

1. Update `productListAction()` to read query parameters:
   ```php
   private function productListAction(Request $request): Response
   {
       $sort = $request->getQuery('sort', 'name');
       $order = $request->getQuery('order', 'asc');
       $category = $request->getQuery('category');

       // In real app, use these for database query
       // For now, just display them

       $info = "Sort: $sort, Order: $order";
       if ($category) {
           $info .= ", Category: $category";
       }

       // ... (rest of your HTML, include $info somewhere)
   }
   ```

2. Test these URLs:
   - http://localhost:8000/products?sort=price
   - http://localhost:8000/products?sort=price&order=desc
   - http://localhost:8000/products?category=electronics

**Expected Result:** Query parameters are read and displayed.

## Exercise 4: Add POST Request Handling

**Goal:** Create a new endpoint that accepts POST data.

**Task:** Add a `/newsletter` subscription endpoint.

**Steps:**

1. Add two routes (GET for form, POST for submission):
   ```php
   if ($uri === '/newsletter' && $method === 'GET') {
       return $this->newsletterFormAction($request);
   }
   if ($uri === '/newsletter' && $method === 'POST') {
       return $this->newsletterSubscribeAction($request);
   }
   ```

2. Add the form action:
   ```php
   private function newsletterFormAction(Request $request): Response
   {
       $html = <<<HTML
   <!DOCTYPE html>
   <html>
   <body>
       <h1>Subscribe to Newsletter</h1>
       <form method="POST" action="/newsletter">
           <input type="email" name="email" required placeholder="your@email.com">
           <button type="submit">Subscribe</button>
       </form>
   </body>
   </html>
   HTML;
       return new Response($html);
   }
   ```

3. Add the subscribe action:
   ```php
   private function newsletterSubscribeAction(Request $request): Response
   {
       $email = $request->getRequest('email', '');

       // Validate email
       if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
           $html = '<h1>Error: Invalid email</h1>';
           return new Response($html, 400);
       }

       // In real app: save to database
       $html = "<h1>Success!</h1><p>Subscribed: $email</p>";
       return new Response($html);
   }
   ```

4. Test: Submit the form with valid/invalid emails

**Challenge:** Add CSRF protection (we'll cover this in later chapters).

## Exercise 5: JSON API Endpoint

**Goal:** Create a RESTful JSON API endpoint.

**Task:** Add CRUD endpoints for products API.

**Steps:**

1. Add routes:
   ```php
   // List all products (already exists)
   if ($uri === '/api/products' && $method === 'GET') {
       return $this->apiProductsAction($request);
   }

   // Get single product
   if (preg_match('#^/api/products/(\d+)$#', $uri, $matches) && $method === 'GET') {
       return $this->apiProductDetailAction($request, $matches[1]);
   }

   // Create product
   if ($uri === '/api/products' && $method === 'POST') {
       return $this->apiProductCreateAction($request);
   }
   ```

2. Implement actions:
   ```php
   private function apiProductDetailAction(Request $request, string $id): Response
   {
       $products = [
           1 => ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
           2 => ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
       ];

       $product = $products[(int)$id] ?? null;

       if (!$product) {
           return Response::json(['error' => 'Product not found'], 404);
       }

       return Response::json(['data' => $product]);
   }

   private function apiProductCreateAction(Request $request): Response
   {
       // In real app: parse JSON body, validate, save to database
       $name = $request->getRequest('name', '');
       $price = $request->getRequest('price', 0);

       if (!$name || !$price) {
           return Response::json(['error' => 'Name and price required'], 400);
       }

       // Simulate creating
       $product = ['id' => 5, 'name' => $name, 'price' => $price];

       return Response::json(['data' => $product], 201);
   }
   ```

3. Test with curl:
   ```bash
   # Get all products
   curl http://localhost:8000/api/products

   # Get single product
   curl http://localhost:8000/api/products/1

   # Create product
   curl -X POST http://localhost:8000/api/products \
     -d "name=Tablet&price=499.99"
   ```

**Expected Result:** JSON responses with correct status codes.

## Exercise 6: Add Middleware-like Logging

**Goal:** Log all requests to a file.

**Task:** Add request logging to the Framework.

**Steps:**

1. Add a `log()` method to Framework:
   ```php
   private function log(Request $request, Response $response): void
   {
       $logFile = __DIR__ . '/../logs/requests.log';
       $dir = dirname($logFile);

       if (!is_dir($dir)) {
           mkdir($dir, 0755, true);
       }

       $time = date('Y-m-d H:i:s');
       $method = $request->getMethod();
       $uri = $request->getUri();
       $status = $response->getStatusCode();

       $line = "[$time] $method $uri - $status\n";

       file_put_contents($logFile, $line, FILE_APPEND);
   }
   ```

2. Call it in `handle()` before returning:
   ```php
   public function handle(Request $request): Response
   {
       $uri = $request->getUri();
       // ... routing logic ...

       // Call appropriate action
       $response = /* ... */;

       // Log the request
       $this->log($request, $response);

       return $response;
   }
   ```

3. Test: Make several requests, then check `logs/requests.log`

**Expected Result:** File contains log entries like:
```
[2024-03-15 14:30:22] GET / - 200
[2024-03-15 14:30:25] GET /products - 200
[2024-03-15 14:30:28] GET /nonexistent - 404
```

## Exercise 7: Error Handling

**Goal:** Add centralized error handling.

**Task:** Wrap request handling in try/catch.

**Steps:**

1. Modify `handle()` method:
   ```php
   public function handle(Request $request): Response
   {
       try {
           $uri = $request->getUri();
           // ... existing routing logic ...

       } catch (\Throwable $e) {
           return $this->errorAction($e);
       }
   }
   ```

2. Add error action:
   ```php
   private function errorAction(\Throwable $e): Response
   {
       // In production: log error, show friendly message
       // In development: show detailed error

       $html = <<<HTML
   <!DOCTYPE html>
   <html>
   <body>
       <h1>500 - Internal Server Error</h1>
       <p>Something went wrong!</p>
       <pre>{$e->getMessage()}</pre>
       <pre>{$e->getTraceAsString()}</pre>
   </body>
   </html>
   HTML;

       return new Response($html, 500);
   }
   ```

3. Test: Throw an exception in one of your actions:
   ```php
   private function homeAction(Request $request): Response
   {
       throw new \Exception('Test error!');
   }
   ```

**Expected Result:** Error page displays instead of fatal error.

## Exercise 8: Redirect Response

**Goal:** Implement redirect functionality.

**Task:** Redirect from old URL to new URL.

**Steps:**

1. Add route:
   ```php
   if ($uri === '/old-page') {
       return Response::redirect('/about', 301);
   }
   ```

2. Test: Visit http://localhost:8000/old-page

**Expected Result:** Browser redirects to `/about` page.

**Challenge:** Implement a "redirect map" for multiple old URLs:
```php
private array $redirects = [
    '/old-page' => '/about',
    '/old-products' => '/products',
    '/contact-us' => '/contact',
];
```

## Exercise 9: Custom Response Headers

**Goal:** Add custom headers to responses.

**Task:** Add cache headers for static-like pages.

**Steps:**

1. Modify an action to add headers:
   ```php
   private function aboutAction(Request $request): Response
   {
       $html = '...';
       $response = new Response($html);

       // Add cache headers (cache for 1 hour)
       $response->setHeader('Cache-Control', 'public, max-age=3600');
       $response->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

       return $response;
   }
   ```

2. Test: Check response headers in browser DevTools (Network tab)

**Expected Result:** Response includes cache headers.

## Exercise 10: Template Rendering

**Goal:** Extract HTML templates to separate files.

**Task:** Create a simple template rendering system.

**Steps:**

1. Create `templates/` directory with `home.php`:
   ```php
   <!DOCTYPE html>
   <html>
   <head>
       <title><?= $title ?? 'Home' ?></title>
   </head>
   <body>
       <h1><?= $heading ?></h1>
       <p><?= $message ?></p>
   </body>
   </html>
   ```

2. Add render method to Framework:
   ```php
   private function render(string $template, array $data = []): string
   {
       $templatePath = __DIR__ . "/../templates/$template.php";

       if (!file_exists($templatePath)) {
           throw new \Exception("Template not found: $template");
       }

       // Extract variables
       extract($data);

       // Capture output
       ob_start();
       require $templatePath;
       return ob_get_clean();
   }
   ```

3. Use it in actions:
   ```php
   private function homeAction(Request $request): Response
   {
       $html = $this->render('home', [
           'title' => 'Homepage',
           'heading' => 'Welcome!',
           'message' => 'This is rendered from a template.',
       ]);

       return new Response($html);
   }
   ```

4. Test: Page renders using template

**Expected Result:** Clean separation of logic and presentation!

## Challenge Exercises

### Challenge 1: RESTful Resource Routing

Create a helper method that automatically maps REST conventions:

```php
// Maps /users → list, /users/123 → show, etc.
$this->resource('users', UserController::class);
```

### Challenge 2: Middleware Pipeline

Implement a middleware system:

```php
$framework->addMiddleware(new LoggingMiddleware());
$framework->addMiddleware(new AuthenticationMiddleware());
```

### Challenge 3: Route Caching

Cache compiled routes for better performance:
- Parse all routes once
- Store in cache file
- Load from cache in production

### Challenge 4: Subdomain Routing

Support subdomain-based routing:
- `api.example.com` → API routes
- `admin.example.com` → Admin routes
- `www.example.com` → Public routes

## Testing Your Changes

After each exercise, run:

```bash
# 1. Test script
php test.php

# 2. Start server
cd public && php -S localhost:8000

# 3. Test in browser
# Visit http://localhost:8000

# 4. Test with curl
curl http://localhost:8000/your-new-route
```

## Solutions

Solutions to these exercises can be found in the `solutions/` directory (create your own!).

## Next Steps

Once you've completed these exercises:

1. Read **Chapter 03: Router** - Extract routing to a dedicated class
2. Read **Chapter 04: Controllers** - Organize actions into controller classes
3. Read **Chapter 05: Dependency Injection** - Automatic dependency resolution

## Key Takeaways

After completing these exercises, you should understand:

- How to add new routes to the Framework
- How to handle different HTTP methods (GET, POST, etc.)
- How to extract route parameters
- How to work with query strings and form data
- How to return different response types (HTML, JSON, redirects)
- How to add middleware-like functionality
- How to implement error handling
- How to separate templates from logic

These concepts form the foundation of all web frameworks!
