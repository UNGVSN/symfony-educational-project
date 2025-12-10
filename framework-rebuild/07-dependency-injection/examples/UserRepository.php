<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

/**
 * Example repository demonstrating dependency injection.
 */
class UserRepository
{
    public function __construct(
        private readonly PDO $connection
    ) {
    }

    /**
     * Finds all users.
     *
     * @return array<array>
     */
    public function findAll(): array
    {
        $stmt = $this->connection->query('SELECT * FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Finds a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Saves a user.
     *
     * @param array $user
     * @return bool
     */
    public function save(array $user): bool
    {
        // Simplified save logic
        return true;
    }
}
