<?php

/**
 * EVOLUTION STEP 2: Front Controller with Functions
 *
 * This improves on the naive implementation by extracting business logic
 * into controller functions. Routing is still if/else, but at least
 * the logic is separated.
 *
 * IMPROVEMENTS:
 * - Separation of routing and logic
 * - Reusable functions
 * - Cleaner code
 *
 * PROBLEMS:
 * - Still procedural
 * - No Request/Response objects
 * - Hard to add middleware
 */

// ============================================================================
// FILE: public/index.php
// ============================================================================

session_start();
require '../config.php';
require '../db.php';
require '../controllers.php';

// Get requested URI
$uri = $_SERVER['REQUEST_URI'];
$uri = strtok($uri, '?');

// Route to appropriate controller function
if ($uri === '/') {
    homeController();

} elseif ($uri === '/about') {
    aboutController();

} elseif ($uri === '/products') {
    productListController();

} elseif (preg_match('#^/products/(\d+)$#', $uri, $matches)) {
    productDetailController($matches[1]);

} else {
    notFoundController($uri);
}

// ============================================================================
// FILE: controllers.php
// ============================================================================

/**
 * Homepage controller
 */
function homeController() {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Homepage</title></head>
    <body>
        <h1>Welcome</h1>
        <?php renderNav(); ?>
        <p>Welcome to our site!</p>
    </body>
    </html>
    <?php
}

/**
 * About page controller
 */
function aboutController() {
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>About</title></head>
    <body>
        <h1>About Us</h1>
        <?php renderNav(); ?>
        <p>We are a great company!</p>
    </body>
    </html>
    <?php
}

/**
 * Product list controller
 */
function productListController() {
    global $db;

    $result = $db->query("SELECT * FROM products");
    $products = $result->fetch_all(MYSQLI_ASSOC);

    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Products</title></head>
    <body>
        <h1>Products</h1>
        <?php renderNav(); ?>

        <?php foreach ($products as $product): ?>
            <div>
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p>$<?= $product['price'] ?></p>
                <a href="/products/<?= $product['id'] ?>">View Details</a>
            </div>
        <?php endforeach; ?>
    </body>
    </html>
    <?php
}

/**
 * Product detail controller
 */
function productDetailController($id) {
    global $db;

    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        notFoundController("/products/$id");
        return;
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head><title><?= htmlspecialchars($product['name']) ?></title></head>
    <body>
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <?php renderNav(); ?>

        <p><strong>Price:</strong> $<?= $product['price'] ?></p>
        <p><?= htmlspecialchars($product['description']) ?></p>

        <a href="/products">← Back to products</a>
    </body>
    </html>
    <?php
}

/**
 * 404 Not Found controller
 */
function notFoundController($uri) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>404 Not Found</title></head>
    <body>
        <h1>404 - Page Not Found</h1>
        <?php renderNav(); ?>
        <p>The page <code><?= htmlspecialchars($uri) ?></code> does not exist.</p>
    </body>
    </html>
    <?php
}

/**
 * Render navigation (reusable component)
 */
function renderNav() {
    ?>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/products">Products</a>
    </nav>
    <?php
}

/**
 * IMPROVEMENTS:
 * + Separation of routing and logic
 * + Reusable functions
 * + Shared components (renderNav)
 * + Easier to maintain
 *
 * PROBLEMS:
 * - Still procedural (not OOP)
 * - Global variables ($db)
 * - No Request/Response abstraction
 * - Hard to test (relies on globals)
 * - Can't easily add middleware
 * - Routing still hardcoded if/else
 *
 * NEXT STEP: Object-Oriented approach with Request/Response
 */
