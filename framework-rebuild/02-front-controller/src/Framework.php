<?php

namespace Framework;

/**
 * Framework is a simple front controller implementation.
 *
 * This class demonstrates the evolution from procedural PHP to OOP:
 * - Receives a Request object
 * - Routes the request to appropriate handler
 * - Returns a Response object
 *
 * Evolution of routing:
 * 1. Simple if/else (current implementation)
 * 2. Route array with callbacks
 * 3. Separate Router class (Chapter 03)
 * 4. Controller classes (Chapter 04)
 * 5. Dependency injection (Chapter 05)
 */
class Framework
{
    /**
     * Handle an HTTP request and return a response.
     *
     * This is the core of the front controller pattern:
     * - Single entry point for all requests
     * - Routing logic to determine which code to execute
     * - Always returns a Response object
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Simple routing: match URI to action methods
        // In later chapters, we'll extract this to a Router class

        // Homepage
        if ($uri === '/' && $method === 'GET') {
            return $this->homeAction($request);
        }

        // About page
        if ($uri === '/about' && $method === 'GET') {
            return $this->aboutAction($request);
        }

        // Contact page (show form)
        if ($uri === '/contact' && $method === 'GET') {
            return $this->contactFormAction($request);
        }

        // Contact page (submit form)
        if ($uri === '/contact' && $method === 'POST') {
            return $this->contactSubmitAction($request);
        }

        // Product list
        if ($uri === '/products' && $method === 'GET') {
            return $this->productListAction($request);
        }

        // Product detail with parameter
        // Pattern: /products/123
        if (preg_match('#^/products/(\d+)$#', $uri, $matches) && $method === 'GET') {
            $productId = $matches[1];
            return $this->productDetailAction($request, $productId);
        }

        // API endpoint example
        if ($uri === '/api/products' && $method === 'GET') {
            return $this->apiProductsAction($request);
        }

        // No route matched - 404
        return $this->notFoundAction($request);
    }

    /**
     * Homepage action.
     */
    private function homeAction(Request $request): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Homepage - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        h1 { color: #333; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        nav a:hover { text-decoration: underline; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
        <a href="/api/products">API</a>
    </nav>

    <h1>Welcome to the Front Controller Demo</h1>

    <div class="info">
        <strong>How it works:</strong>
        <ul>
            <li>All requests go through <code>public/index.php</code></li>
            <li>The Framework class routes requests to action methods</li>
            <li>Each action returns a Response object</li>
            <li>Clean URLs thanks to .htaccess rewriting</li>
        </ul>
    </div>

    <p>This is a simple front controller implementation demonstrating:</p>
    <ul>
        <li>Single entry point (index.php)</li>
        <li>Request/Response abstraction</li>
        <li>Basic routing with if/else</li>
        <li>Route parameters (e.g., /products/123)</li>
        <li>Different HTTP methods (GET, POST)</li>
    </ul>

    <h2>Try these URLs:</h2>
    <ul>
        <li><a href="/">/</a> - Homepage (you are here)</li>
        <li><a href="/about">/about</a> - About page</li>
        <li><a href="/contact">/contact</a> - Contact form</li>
        <li><a href="/products">/products</a> - Product list</li>
        <li><a href="/products/1">/products/1</a> - Product detail</li>
        <li><a href="/products/42">/products/42</a> - Another product</li>
        <li><a href="/api/products">/api/products</a> - JSON API</li>
        <li><a href="/nonexistent">/nonexistent</a> - 404 error</li>
    </ul>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * About page action.
     */
    private function aboutAction(Request $request): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>About - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>About This Framework</h1>
    <p>This is a minimal framework implementation demonstrating the Front Controller pattern.</p>

    <h2>Architecture</h2>
    <pre>
HTTP Request
    ↓
public/index.php (Front Controller)
    ↓
Framework->handle(Request)
    ↓
Routing Logic (if/else)
    ↓
Action Method
    ↓
Response->send()
    ↓
HTTP Response
    </pre>

    <h2>Key Components</h2>
    <ul>
        <li><strong>Request:</strong> Encapsulates HTTP request data</li>
        <li><strong>Response:</strong> Encapsulates HTTP response</li>
        <li><strong>Framework:</strong> Routes requests to actions</li>
        <li><strong>Front Controller:</strong> Single entry point (index.php)</li>
    </ul>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * Contact form action (GET).
     */
    private function contactFormAction(Request $request): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contact - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        form { margin: 20px 0; }
        label { display: block; margin: 10px 0 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        button { margin-top: 10px; padding: 10px 20px; background: #0066cc; color: white; border: none; cursor: pointer; }
        button:hover { background: #0052a3; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>Contact Us</h1>

    <form method="POST" action="/contact">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="message">Message:</label>
        <textarea id="message" name="message" rows="5" required></textarea>

        <button type="submit">Send Message</button>
    </form>

    <p><em>Note: This demonstrates POST request handling. The form data will be processed by the same route with POST method.</em></p>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * Contact form submit action (POST).
     */
    private function contactSubmitAction(Request $request): Response
    {
        $name = $request->getRequest('name', '');
        $email = $request->getRequest('email', '');
        $message = $request->getRequest('message', '');

        // In a real application, you would:
        // - Validate the data
        // - Save to database
        // - Send email
        // - Redirect to success page

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thank You - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0; }
        .data { background: #f8f9fa; padding: 15px; margin: 20px 0; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>Thank You!</h1>

    <div class="success">
        <strong>Your message has been received.</strong>
    </div>

    <div class="data">
        <h3>Submitted Data:</h3>
        <p><strong>Name:</strong> {$this->escape($name)}</p>
        <p><strong>Email:</strong> {$this->escape($email)}</p>
        <p><strong>Message:</strong> {$this->escape($message)}</p>
    </div>

    <p><em>Note: In a real application, this data would be validated, saved to a database, and/or sent via email.</em></p>

    <p><a href="/contact">Send another message</a></p>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * Product list action.
     */
    private function productListAction(Request $request): Response
    {
        // Simulate product data
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
            ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 79.99],
            ['id' => 4, 'name' => 'Monitor', 'price' => 299.99],
        ];

        $productRows = '';
        foreach ($products as $product) {
            $productRows .= sprintf(
                '<tr><td>%d</td><td><a href="/products/%d">%s</a></td><td>$%.2f</td></tr>',
                $product['id'],
                $product['id'],
                $this->escape($product['name']),
                $product['price']
            );
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Products - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>Our Products</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            $productRows
        </tbody>
    </table>

    <p><em>Click on a product name to view details.</em></p>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * Product detail action.
     *
     * Demonstrates route parameters: /products/{id}
     */
    private function productDetailAction(Request $request, string $productId): Response
    {
        // Simulate product database
        $products = [
            1 => ['id' => 1, 'name' => 'Laptop', 'price' => 999.99, 'description' => 'High-performance laptop'],
            2 => ['id' => 2, 'name' => 'Mouse', 'price' => 29.99, 'description' => 'Wireless optical mouse'],
            3 => ['id' => 3, 'name' => 'Keyboard', 'price' => 79.99, 'description' => 'Mechanical keyboard'],
            4 => ['id' => 4, 'name' => 'Monitor', 'price' => 299.99, 'description' => '27-inch 4K monitor'],
        ];

        $product = $products[(int)$productId] ?? null;

        if (!$product) {
            return $this->notFoundAction($request);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$this->escape($product['name'])} - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        .product { background: #f8f9fa; padding: 20px; margin: 20px 0; border: 1px solid #ddd; }
        .price { font-size: 24px; color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>{$this->escape($product['name'])}</h1>

    <div class="product">
        <p><strong>Product ID:</strong> {$product['id']}</p>
        <p><strong>Description:</strong> {$this->escape($product['description'])}</p>
        <p class="price">\${$product['price']}</p>
    </div>

    <p><a href="/products">← Back to product list</a></p>

    <p><em>Note: This demonstrates route parameters. The product ID ({$productId}) was extracted from the URL.</em></p>
</body>
</html>
HTML;

        return new Response($html);
    }

    /**
     * API endpoint example - returns JSON.
     */
    private function apiProductsAction(Request $request): Response
    {
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
            ['id' => 2, 'name' => 'Mouse', 'price' => 29.99],
            ['id' => 3, 'name' => 'Keyboard', 'price' => 79.99],
            ['id' => 4, 'name' => 'Monitor', 'price' => 299.99],
        ];

        // Use the Response::json() helper method
        return Response::json([
            'success' => true,
            'data' => $products,
            'count' => count($products),
        ]);
    }

    /**
     * 404 Not Found action.
     */
    private function notFoundAction(Request $request): Response
    {
        $requestedUri = $this->escape($request->getUri());

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>404 Not Found - Framework Demo</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 0 20px; }
        nav { margin: 20px 0; padding: 10px; background: #f0f0f0; }
        nav a { margin-right: 15px; text-decoration: none; color: #0066cc; }
        .error { background: #f8d7da; padding: 20px; border-left: 4px solid #dc3545; margin: 20px 0; }
        h1 { color: #dc3545; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
        <a href="/products">Products</a>
    </nav>

    <h1>404 - Page Not Found</h1>

    <div class="error">
        <p>The requested page <code>$requestedUri</code> could not be found.</p>
    </div>

    <p>This happened because no route matched your request in the Framework class.</p>

    <h3>Available routes:</h3>
    <ul>
        <li>GET / - Homepage</li>
        <li>GET /about - About page</li>
        <li>GET /contact - Contact form</li>
        <li>POST /contact - Submit contact form</li>
        <li>GET /products - Product list</li>
        <li>GET /products/{id} - Product detail</li>
        <li>GET /api/products - JSON API</li>
    </ul>

    <p><a href="/">Go to homepage</a></p>
</body>
</html>
HTML;

        return new Response($html, 404);
    }

    /**
     * Escape HTML to prevent XSS attacks.
     *
     * Always escape user input before displaying it!
     */
    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
