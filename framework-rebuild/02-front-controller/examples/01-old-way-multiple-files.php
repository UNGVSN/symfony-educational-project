<?php

/**
 * THE OLD WAY: Multiple PHP Files
 *
 * This demonstrates the traditional PHP approach before front controllers.
 * Each page is a separate file with duplicate initialization code.
 *
 * Project structure:
 *
 * website/
 * ├── index.php          # Homepage
 * ├── about.php          # About page
 * ├── products.php       # Product list
 * ├── product-detail.php # Product detail
 * └── contact.php        # Contact form
 *
 * URLs:
 * - http://example.com/index.php
 * - http://example.com/about.php
 * - http://example.com/products.php
 * - http://example.com/product-detail.php?id=123
 * - http://example.com/contact.php
 *
 * PROBLEMS:
 * 1. Code duplication (each file has session_start, includes, etc.)
 * 2. Inconsistent initialization
 * 3. Ugly URLs with .php extensions
 * 4. Hard to add global features (logging, authentication)
 * 5. No central error handling
 */

// ============================================================================
// FILE: index.php
// ============================================================================

// Common initialization code (DUPLICATED in every file!)
session_start();
require 'config.php';
require 'db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Homepage</title>
</head>
<body>
    <h1>Welcome to My Site</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Products</a>
        <a href="contact.php">Contact</a>
    </nav>
    <p>This is the homepage.</p>
</body>
</html>

// ============================================================================
// FILE: about.php
// ============================================================================

// Same initialization code DUPLICATED again!
session_start();
require 'config.php';
require 'db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>About</title>
</head>
<body>
    <h1>About Us</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Products</a>
        <a href="contact.php">Contact</a>
    </nav>
    <p>We are a great company!</p>
</body>
</html>

// ============================================================================
// FILE: products.php
// ============================================================================

// Same initialization code DUPLICATED yet again!
session_start();
require 'config.php';
require 'db.php';

// Query database
$result = $db->query("SELECT * FROM products");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
</head>
<body>
    <h1>Our Products</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Products</a>
        <a href="contact.php">Contact</a>
    </nav>

    <?php while ($product = $result->fetch_assoc()): ?>
        <div>
            <h2><?= htmlspecialchars($product['name']) ?></h2>
            <p>$<?= $product['price'] ?></p>
            <a href="product-detail.php?id=<?= $product['id'] ?>">View Details</a>
        </div>
    <?php endwhile; ?>
</body>
</html>

// ============================================================================
// FILE: product-detail.php
// ============================================================================

// Same initialization code... you get the idea
session_start();
require 'config.php';
require 'db.php';

// Get product ID from URL
$id = $_GET['id'] ?? 0;

// Query database
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    echo "Product not found";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <p>$<?= $product['price'] ?></p>
    <p><?= htmlspecialchars($product['description']) ?></p>
    <a href="products.php">← Back to products</a>
</body>
</html>

/**
 * SUMMARY OF PROBLEMS:
 *
 * 1. CODE DUPLICATION
 *    Every file has: session_start(), require statements, navigation
 *
 * 2. INCONSISTENT INITIALIZATION
 *    Easy to forget session_start() in one file
 *    Easy to have different include order
 *
 * 3. UGLY URLs
 *    /product-detail.php?id=123 instead of /products/123
 *
 * 4. HARD TO ADD GLOBAL FEATURES
 *    Want to add authentication? Update every file!
 *    Want to add logging? Update every file!
 *    Want to add error handling? Update every file!
 *
 * 5. SECURITY ISSUES
 *    Each file directly accessible
 *    config.php might be accessible if extension disabled
 *    No central input validation
 *
 * 6. MAINTENANCE NIGHTMARE
 *    Change navigation? Update every file!
 *    Change database connection? Update every file!
 *    Add new feature? Update every file!
 *
 * THE SOLUTION: Front Controller Pattern
 * - Single entry point (index.php)
 * - All initialization in one place
 * - Clean URLs (/products/123)
 * - Easy to add global features
 * - Better security
 * - Easy to maintain
 */
