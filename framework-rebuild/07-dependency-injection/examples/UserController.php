<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\UserService;
use Psr\Log\LoggerInterface;

/**
 * Example controller demonstrating autowiring.
 */
class UserController
{
    /**
     * Constructor injection - dependencies autowired automatically.
     */
    public function __construct(
        private readonly UserService $userService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Lists all users.
     *
     * @return string
     */
    public function list(): string
    {
        $this->logger->info('Listing users');

        $users = $this->userService->getAllUsers();

        return json_encode($users);
    }

    /**
     * Shows a user.
     *
     * @param int $id
     * @return string
     */
    public function show(int $id): string
    {
        $this->logger->info('Showing user', ['id' => $id]);

        $user = $this->userService->getUser($id);

        if (!$user) {
            return json_encode(['error' => 'User not found']);
        }

        return json_encode($user);
    }

    /**
     * Creates a user.
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    public function create(string $email, string $name): string
    {
        $this->logger->info('Creating user', ['email' => $email]);

        $user = $this->userService->createUser($email, $name);

        return json_encode($user);
    }
}
