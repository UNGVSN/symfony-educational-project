<?php

namespace Framework\Security\Core\Authentication\Token;

use Framework\Security\Core\User\UserInterface;

/**
 * Abstract base token implementation.
 *
 * This class provides common functionality for security tokens.
 * Concrete token classes should extend this.
 */
abstract class AbstractToken implements TokenInterface
{
    private bool $authenticated = false;
    private array $attributes = [];

    /**
     * @param UserInterface $user The user
     * @param string $firewallName The firewall name
     * @param array<string> $roles User roles
     */
    public function __construct(
        private UserInterface $user,
        private string $firewallName,
        private array $roles = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function setUser(UserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoleNames(): array
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthenticated(bool $authenticated): void
    {
        $this->authenticated = $authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {
        $this->user->eraseCredentials();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * String representation of the token.
     *
     * @return string
     */
    public function __toString(): string
    {
        $class = static::class;
        $user = $this->user->getUserIdentifier();
        $authenticated = $this->authenticated ? 'authenticated' : 'not authenticated';
        $roles = implode(', ', $this->roles);

        return sprintf(
            '%s(user: "%s", %s, roles: [%s])',
            $class,
            $user,
            $authenticated,
            $roles
        );
    }

    /**
     * Serialize the token.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'user' => $this->user,
            'firewallName' => $this->firewallName,
            'roles' => $this->roles,
            'authenticated' => $this->authenticated,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * Unserialize the token.
     *
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->user = $data['user'];
        $this->firewallName = $data['firewallName'];
        $this->roles = $data['roles'];
        $this->authenticated = $data['authenticated'];
        $this->attributes = $data['attributes'];
    }
}
