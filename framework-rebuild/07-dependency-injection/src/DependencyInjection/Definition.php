<?php

declare(strict_types=1);

namespace App\DependencyInjection;

/**
 * Service definition.
 *
 * Describes how to create and configure a service.
 */
class Definition
{
    /**
     * @var array<mixed> Constructor arguments
     */
    private array $arguments = [];

    /**
     * @var array<array{method: string, arguments: array<mixed>}> Method calls
     */
    private array $methodCalls = [];

    /**
     * @var array<string, array<string, mixed>> Service tags
     */
    private array $tags = [];

    /**
     * @var callable|null Factory to create the service
     */
    private $factory = null;

    /**
     * @var bool Whether the service is public (can be retrieved from container)
     */
    private bool $public = true;

    /**
     * @var bool Whether the service is shared (singleton)
     */
    private bool $shared = true;

    /**
     * @var bool Whether the service is autowired
     */
    private bool $autowired = false;

    /**
     * @var bool Whether the service is lazy loaded
     */
    private bool $lazy = false;

    /**
     * @var bool Whether the service is synthetic (set at runtime)
     */
    private bool $synthetic = false;

    /**
     * @var bool Whether the service is abstract (template for other definitions)
     */
    private bool $abstract = false;

    /**
     * @var string|null Parent definition to inherit from
     */
    private ?string $parent = null;

    /**
     * @param string|null $class The service class name
     * @param array<mixed> $arguments Constructor arguments
     */
    public function __construct(
        private ?string $class = null,
        array $arguments = []
    ) {
        $this->arguments = $arguments;
    }

    /**
     * Gets the service class name.
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Sets the service class name.
     *
     * @param string|null $class
     * @return $this
     */
    public function setClass(?string $class): self
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Gets constructor arguments.
     *
     * @return array<mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Sets constructor arguments.
     *
     * @param array<mixed> $arguments
     * @return $this
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Sets a specific argument by index.
     *
     * @param int $index
     * @param mixed $argument
     * @return $this
     */
    public function setArgument(int $index, mixed $argument): self
    {
        $this->arguments[$index] = $argument;
        return $this;
    }

    /**
     * Adds a method call to be executed after instantiation.
     *
     * @param string $method The method name
     * @param array<mixed> $arguments The method arguments
     * @return $this
     */
    public function addMethodCall(string $method, array $arguments = []): self
    {
        $this->methodCalls[] = [
            'method' => $method,
            'arguments' => $arguments,
        ];
        return $this;
    }

    /**
     * Gets all method calls.
     *
     * @return array<array{method: string, arguments: array<mixed>}>
     */
    public function getMethodCalls(): array
    {
        return $this->methodCalls;
    }

    /**
     * Adds a tag to the service.
     *
     * @param string $name Tag name
     * @param array<string, mixed> $attributes Tag attributes
     * @return $this
     */
    public function addTag(string $name, array $attributes = []): self
    {
        $this->tags[$name][] = $attributes;
        return $this;
    }

    /**
     * Gets all tags.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Checks if the service has a specific tag.
     *
     * @param string $name
     * @return bool
     */
    public function hasTag(string $name): bool
    {
        return isset($this->tags[$name]);
    }

    /**
     * Gets a specific tag.
     *
     * @param string $name
     * @return array<array<string, mixed>>
     */
    public function getTag(string $name): array
    {
        return $this->tags[$name] ?? [];
    }

    /**
     * Sets the factory for creating the service.
     *
     * @param callable|array{class-string|object, string}|null $factory
     * @return $this
     */
    public function setFactory(callable|array|null $factory): self
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Gets the factory.
     *
     * @return callable|array|null
     */
    public function getFactory(): callable|array|null
    {
        return $this->factory;
    }

    /**
     * Sets whether the service is public.
     *
     * @param bool $public
     * @return $this
     */
    public function setPublic(bool $public): self
    {
        $this->public = $public;
        return $this;
    }

    /**
     * Checks if the service is public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * Sets whether the service is shared (singleton).
     *
     * @param bool $shared
     * @return $this
     */
    public function setShared(bool $shared): self
    {
        $this->shared = $shared;
        return $this;
    }

    /**
     * Checks if the service is shared.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * Sets whether the service is autowired.
     *
     * @param bool $autowired
     * @return $this
     */
    public function setAutowired(bool $autowired): self
    {
        $this->autowired = $autowired;
        return $this;
    }

    /**
     * Checks if the service is autowired.
     *
     * @return bool
     */
    public function isAutowired(): bool
    {
        return $this->autowired;
    }

    /**
     * Sets whether the service is lazy loaded.
     *
     * @param bool $lazy
     * @return $this
     */
    public function setLazy(bool $lazy): self
    {
        $this->lazy = $lazy;
        return $this;
    }

    /**
     * Checks if the service is lazy.
     *
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->lazy;
    }

    /**
     * Sets whether the service is synthetic.
     *
     * @param bool $synthetic
     * @return $this
     */
    public function setSynthetic(bool $synthetic): self
    {
        $this->synthetic = $synthetic;
        return $this;
    }

    /**
     * Checks if the service is synthetic.
     *
     * @return bool
     */
    public function isSynthetic(): bool
    {
        return $this->synthetic;
    }

    /**
     * Sets whether the service is abstract.
     *
     * @param bool $abstract
     * @return $this
     */
    public function setAbstract(bool $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Checks if the service is abstract.
     *
     * @return bool
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Sets the parent definition.
     *
     * @param string|null $parent
     * @return $this
     */
    public function setParent(?string $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Gets the parent definition.
     *
     * @return string|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }
}
