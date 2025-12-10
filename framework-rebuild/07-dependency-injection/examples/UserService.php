<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Example service demonstrating constructor and setter injection.
 */
class UserService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly UserRepository $repository
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * Setter injection for optional dependencies.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Creates a new user.
     *
     * @param string $email
     * @param string $name
     * @return array
     */
    public function createUser(string $email, string $name): array
    {
        $this->logger->info('Creating user', ['email' => $email]);

        $user = [
            'email' => $email,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->repository->save($user);

        $this->logger->info('User created', ['email' => $email]);

        return $user;
    }

    /**
     * Gets all users.
     *
     * @return array<array>
     */
    public function getAllUsers(): array
    {
        return $this->repository->findAll();
    }

    /**
     * Gets a user by ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getUser(int $id): ?array
    {
        return $this->repository->findById($id);
    }
}
