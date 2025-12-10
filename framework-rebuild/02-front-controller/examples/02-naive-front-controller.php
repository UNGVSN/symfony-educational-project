<?php

/**
 * EVOLUTION STEP 1: Naive Front Controller
 *
 * This is a simple front controller implementation using if/else statements.
 * All requests go through this single file.
 *
 * IMPROVEMENTS over multiple files:
 * - Single entry point
 * - No code duplication
 * - Clean URLs possible (with .htaccess)
 *
 * PROBLEMS:
 * - All routing logic in one file
 * - Hard to test
 * - No separation of concerns
 * - Becomes unmaintainable with many routes
 */

// index.php

session_start();
require 'config.php';
require 'db.php';

// Get the requested URI
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?'); // Remove query string

// Simple routing with if/else
if ($uri === '/') {
    // Homepage
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Homepage</title></head>
    <body>
        <h1>Welcome</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/products">Products</a>
        </nav>
    </body>
    </html>
    <?php

} elseif ($uri === '/about') {
    // About page
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>About</title></head>
    <body>
        <h1>About Us</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/products">Products</a>
        </nav>
    </body>
    </html>
    <?php

} elseif ($uri === '/products') {
    // Product list
    $result = $db->query("SELECT * FROM products");
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Products</title></head>
    <body>
        <h1>Products</h1>
        <?php while ($product = $result->fetch_assoc()): ?>
            <div>
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <a href="/products/<?= $product['id'] ?>">View</a>
            </div>
        <?php endwhile; ?>
    </body>
    </html>
    <?php

} elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
    // Product detail
    $id = $matches[1];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        http_response_code(404);
        echo "Product not found";
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head><title><?= htmlspecialchars($product['name']) ?></title></head>
    <body>
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p>$<?= $product['price'] ?></p>
    </body>
    </html>
    <?php

} else {
    // 404 Not Found
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>404 Not Found</title></head>
    <body>
        <h1>404 - Page Not Found</h1>
        <p>The page <?= htmlspecialchars($uri) ?> does not exist.</p>
    </body>
    </html>
    <?php
}

/**
 * IMPROVEMENTS:
 * + Single entry point
 * + No code duplication
 * + Clean URLs (/products/123)
 * + Can use regex for dynamic routes
 *
 * PROBLEMS:
 * - All code in one file (unmaintainable)
 * - Routing logic mixed with presentation
 * - Hard to test
 * - Can't reuse code easily
 * - No Request/Response abstraction
 *
 * NEXT STEP: Extract to functions
 */
