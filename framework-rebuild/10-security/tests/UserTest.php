<?php

/**
 * User and UserProvider Tests
 */

require_once __DIR__ . '/../src/Security/Core/User/UserInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/User.php';
require_once __DIR__ . '/../src/Security/Core/User/UserProviderInterface.php';
require_once __DIR__ . '/../src/Security/Core/User/InMemoryUserProvider.php';
require_once __DIR__ . '/../src/Security/Core/Exception/AuthenticationException.php';
require_once __DIR__ . '/../src/Security/Core/Exception/UserNotFoundException.php';

use Framework\Security\Core\User\User;
use Framework\Security\Core\User\InMemoryUserProvider;
use Framework\Security\Core\Exception\UserNotFoundException;

class UserTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "=== User Tests ===\n\n";

        $this->testUserCreation();
        $this->testUserRoles();
        $this->testUserAttributes();
        $this->testInMemoryProvider();
        $this->testProviderNotFound();
        $this->testProviderRefresh();

        echo "\n=== Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
    }

    private function testUserCreation(): void
    {
        echo "Test: User creation\n";

        $user = new User(
            identifier: 'john@example.com',
            password: password_hash('secret', PASSWORD_BCRYPT),
            roles: ['ROLE_ADMIN']
        );

        $this->assert(
            $user->getUserIdentifier() === 'john@example.com',
            'User identifier should match'
        );

        $this->assert(
            password_verify('secret', $user->getPassword()),
            'Password should be hashed correctly'
        );

        echo "\n";
    }

    private function testUserRoles(): void
    {
        echo "Test: User roles\n";

        $user = new User(
            identifier: 'user@example.com',
            password: 'hashed',
            roles: ['ROLE_ADMIN', 'ROLE_EDITOR']
        );

        $roles = $user->getRoles();

        $this->assert(
            in_array('ROLE_ADMIN', $roles),
            'Should have ROLE_ADMIN'
        );

        $this->assert(
            in_array('ROLE_EDITOR', $roles),
            'Should have ROLE_EDITOR'
        );

        $this->assert(
            in_array('ROLE_USER', $roles),
            'Should automatically have ROLE_USER'
        );

        echo "\n";
    }

    private function testUserAttributes(): void
    {
        echo "Test: User attributes\n";

        $user = new User(
            identifier: 'user@example.com',
            password: 'hashed',
            roles: [],
            attributes: ['name' => 'John Doe', 'age' => 30]
        );

        $this->assert(
            $user->getAttribute('name') === 'John Doe',
            'Should get attribute value'
        );

        $this->assert(
            $user->getAttribute('missing', 'default') === 'default',
            'Should return default for missing attribute'
        );

        $user->setAttribute('city', 'New York');

        $this->assert(
            $user->getAttribute('city') === 'New York',
            'Should set attribute value'
        );

        echo "\n";
    }

    private function testInMemoryProvider(): void
    {
        echo "Test: InMemoryUserProvider\n";

        $provider = new InMemoryUserProvider([
            'admin@example.com' => [
                'password' => password_hash('admin', PASSWORD_BCRYPT),
                'roles' => ['ROLE_ADMIN'],
            ],
            'user@example.com' => [
                'password' => password_hash('user', PASSWORD_BCRYPT),
                'roles' => ['ROLE_USER'],
            ],
        ]);

        $user = $provider->loadUserByIdentifier('admin@example.com');

        $this->assert(
            $user->getUserIdentifier() === 'admin@example.com',
            'Should load user by identifier'
        );

        $this->assert(
            in_array('ROLE_ADMIN', $user->getRoles()),
            'Should load user roles'
        );

        echo "\n";
    }

    private function testProviderNotFound(): void
    {
        echo "Test: UserProvider user not found\n";

        $provider = new InMemoryUserProvider([]);

        try {
            $provider->loadUserByIdentifier('nonexistent@example.com');
            $this->assert(false, 'Should throw UserNotFoundException');
        } catch (UserNotFoundException $e) {
            $this->assert(true, 'Should throw UserNotFoundException');
        }

        echo "\n";
    }

    private function testProviderRefresh(): void
    {
        echo "Test: UserProvider refresh\n";

        $provider = new InMemoryUserProvider([
            'user@example.com' => [
                'password' => password_hash('secret', PASSWORD_BCRYPT),
                'roles' => ['ROLE_USER'],
            ],
        ]);

        $user = $provider->loadUserByIdentifier('user@example.com');
        $refreshedUser = $provider->refreshUser($user);

        $this->assert(
            $refreshedUser->getUserIdentifier() === $user->getUserIdentifier(),
            'Refreshed user should have same identifier'
        );

        echo "\n";
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✓ $message\n";
            $this->passed++;
        } else {
            echo "  ✗ $message\n";
            $this->failed++;
        }
    }
}

// Run tests
$test = new UserTest();
$test->run();
