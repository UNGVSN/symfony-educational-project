<?php

/**
 * Basic Login Example
 *
 * This example demonstrates:
 * - Setting up a user provider with in-memory users
 * - Creating a form login authenticator
 * - Configuring a firewall
 * - Handling login requests
 */

require_once __DIR__ . '/../src/Security/Core/User/UserInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/User.php';
require_once __DIR__ . '/../src/Security/Core/User/UserProviderInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/InMemoryUserProvider.php';
require_once __DIR__ . '/../src/Security/Core/Exception/AuthenticationException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/UserNotFoundException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/BadCredentialsException.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/TokenInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/AbstractToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/Token/UsernamePasswordToken.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/TokenStorageInterface.php';
require_once __DIR__ . '/../src/Security/Core/Authentication/TokenStorage.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/Passport/Passport.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/AuthenticatorInterface.php';
require_once __DIR__ . '/../src/Security/Http/Authenticator/FormLoginAuthenticator.php';
require_once __DIR__ . '/../src/Security/Http/Firewall/Firewall.php';

use Framework\Security\Core\User\InMemoryUserProvider;
use Framework\Security\Core\Authentication\TokenStorage;
use Framework\Security\Http\Authenticator\FormLoginAuthenticator;
use Framework\Security\Http\Authenticator\Request;
use Framework\Security\Http\Firewall\Firewall;

echo "=== Basic Login Example ===\n\n";

// 1. Create users
echo "1. Creating user provider with test users...\n";
$userProvider = new InMemoryUserProvider([
    'admin@example.com' => [
        'password' => password_hash('admin123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_ADMIN'],
        'attributes' => ['name' => 'Admin User'],
    ],
    'user@example.com' => [
        'password' => password_hash('user123', PASSWORD_BCRYPT),
        'roles' => ['ROLE_USER'],
        'attributes' => ['name' => 'Regular User'],
    ],
]);

echo "   Users created: " . implode(', ', $userProvider->getAllIdentifiers()) . "\n\n";

// 2. Create token storage
echo "2. Creating token storage...\n";
$tokenStorage = new TokenStorage();
echo "   Token storage created\n\n";

// 3. Create authenticator
echo "3. Creating form login authenticator...\n";
$authenticator = new FormLoginAuthenticator(
    userProvider: $userProvider,
    loginPath: '/login',
    successPath: '/dashboard'
);
echo "   Authenticator configured:\n";
echo "   - Login path: {$authenticator->getLoginPath()}\n";
echo "   - Success path: {$authenticator->getSuccessPath()}\n\n";

// 4. Create firewall
echo "4. Creating firewall...\n";
$firewall = new Firewall(
    pattern: '^/',  // Match all URLs
    authenticators: [$authenticator],
    tokenStorage: $tokenStorage,
    name: 'main'
);
echo "   Firewall created: {$firewall->getName()}\n";
echo "   Pattern: {$firewall->getPattern()}\n\n";

// 5. Simulate login request
echo "5. Simulating login request...\n";
echo "   POST /login\n";
echo "   _username=admin@example.com\n";
echo "   _password=admin123\n\n";

$request = new Request(
    query: [],
    request: [
        '_username' => 'admin@example.com',
        '_password' => 'admin123',
    ],
    server: [
        'REQUEST_METHOD' => 'POST',
        'PATH_INFO' => '/login',
    ]
);

// 6. Handle request
echo "6. Processing authentication...\n";
$response = $firewall->handle($request);

if ($response) {
    echo "   Authentication completed!\n";
    echo "   Response status: {$response->getStatusCode()}\n\n";
}

// 7. Check token
echo "7. Checking authentication token...\n";
$token = $tokenStorage->getToken();

if ($token) {
    echo "   ✓ User authenticated!\n";
    echo "   User: {$token->getUser()->getUserIdentifier()}\n";
    echo "   Roles: " . implode(', ', $token->getRoleNames()) . "\n";
    echo "   Firewall: {$token->getFirewallName()}\n";
    echo "   Authenticated: " . ($token->isAuthenticated() ? 'Yes' : 'No') . "\n\n";
} else {
    echo "   ✗ No authentication token found\n\n";
}

// 8. Test failed login
echo "8. Testing failed login...\n";
echo "   POST /login\n";
echo "   _username=admin@example.com\n";
echo "   _password=wrongpassword\n\n";

$tokenStorage->clear(); // Clear previous token

$failedRequest = new Request(
    query: [],
    request: [
        '_username' => 'admin@example.com',
        '_password' => 'wrongpassword',
    ],
    server: [
        'REQUEST_METHOD' => 'POST',
        'PATH_INFO' => '/login',
    ]
);

$failedResponse = $firewall->handle($failedRequest);

if ($failedResponse) {
    echo "   Authentication failed (as expected)\n";
    echo "   Response status: {$failedResponse->getStatusCode()}\n";
    echo "   Contains error: " . (str_contains($failedResponse->getContent(), 'Failed') ? 'Yes' : 'No') . "\n\n";
}

// 9. Test non-login request
echo "9. Testing non-login request (should skip authentication)...\n";
echo "   GET /dashboard\n\n";

$normalRequest = new Request(
    query: [],
    request: [],
    server: [
        'REQUEST_METHOD' => 'GET',
        'PATH_INFO' => '/dashboard',
    ]
);

$normalResponse = $firewall->handle($normalRequest);

if ($normalResponse === null) {
    echo "   ✓ Request passed through (no authentication triggered)\n\n";
} else {
    echo "   ✗ Unexpected authentication triggered\n\n";
}

echo "=== Example Complete ===\n";
