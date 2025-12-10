<?php

/**
 * Test Runner
 *
 * Runs all security component tests.
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         SYMFONY SECURITY COMPONENT TEST SUITE                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$startTime = microtime(true);

// Run User Tests
echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│ USER AND PROVIDER TESTS                                        │\n";
echo "└────────────────────────────────────────────────────────────────┘\n\n";
require __DIR__ . '/tests/UserTest.php';
echo "\n";

// Run Authorization Tests
echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│ AUTHORIZATION TESTS                                            │\n";
echo "└────────────────────────────────────────────────────────────────┘\n\n";
require __DIR__ . '/tests/AuthorizationTest.php';
echo "\n";

$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║ TESTS COMPLETE                                                 ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║ Duration: {$duration}ms" . str_repeat(' ', 52 - strlen((string)$duration)) . "║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
