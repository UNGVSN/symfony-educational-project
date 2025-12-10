<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use App\DependencyInjection\Compiler\CompilerPassInterface;
use App\DependencyInjection\Exception\FrozenContainerException;
use App\DependencyInjection\Exception\ServiceNotFoundException;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Container builder for defining and compiling services.
 *
 * Used during the build phase to register services and compile
 * them into an optimized runtime container.
 */
class ContainerBuilder extends Container
{
    /**
     * @var array<string, Definition> Service definitions
     */
    private array $definitions = [];

    /**
     * @var array<string, string> Service aliases
     */
    private array $aliases = [];

    /**
     * @var array<CompilerPassInterface> Compiler passes
     */
    private array $compilerPasses = [];

    /**
     * @var bool Whether the container has been compiled
     */
    private bool $compiled = false;

    /**
     * Registers a new service definition.
     *
     * @param string $id The service identifier
     * @param string|null $class The service class name (defaults to $id)
     * @return Definition The service definition
     * @throws FrozenContainerException If container is already compiled
     */
    public function register(string $id, ?string $class = null): Definition
    {
        $this->ensureNotFrozen();

        $definition = new Definition($class ?? $id);
        $this->setDefinition($id, $definition);

        return $definition;
    }

    /**
     * Sets a service definition.
     *
     * @param string $id
     * @param Definition $definition
     * @return $this
     * @throws FrozenContainerException
     */
    public function setDefinition(string $id, Definition $definition): self
    {
        $this->ensureNotFrozen();

        $this->definitions[$id] = $definition;

        return $this;
    }

    /**
     * Gets a service definition.
     *
     * @param string $id
     * @return Definition
     * @throws ServiceNotFoundException
     */
    public function getDefinition(string $id): Definition
    {
        if (!$this->hasDefinition($id)) {
            throw new ServiceNotFoundException($id);
        }

        return $this->definitions[$id];
    }

    /**
     * Checks if a service definition exists.
     *
     * @param string $id
     * @return bool
     */
    public function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Removes a service definition.
     *
     * @param string $id
     * @return $this
     * @throws FrozenContainerException
     */
    public function removeDefinition(string $id): self
    {
        $this->ensureNotFrozen();

        unset($this->definitions[$id]);

        return $this;
    }

    /**
     * Gets all service definitions.
     *
     * @return array<string, Definition>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * Sets a service alias.
     *
     * @param string $alias The alias name
     * @param string $id The service identifier
     * @return $this
     * @throws FrozenContainerException
     */
    public function setAlias(string $alias, string $id): self
    {
        $this->ensureNotFrozen();

        $this->aliases[$alias] = $id;

        return $this;
    }

    /**
     * Gets the service ID for an alias.
     *
     * @param string $alias
     * @return string
     * @throws ServiceNotFoundException
     */
    public function getAlias(string $alias): string
    {
        if (!$this->hasAlias($alias)) {
            throw new ServiceNotFoundException($alias);
        }

        return $this->aliases[$alias];
    }

    /**
     * Checks if an alias exists.
     *
     * @param string $alias
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }

    /**
     * Gets all aliases.
     *
     * @return array<string, string>
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Adds a compiler pass.
     *
     * @param CompilerPassInterface $pass
     * @return $this
     * @throws FrozenContainerException
     */
    public function addCompilerPass(CompilerPassInterface $pass): self
    {
        $this->ensureNotFrozen();

        $this->compilerPasses[] = $pass;

        return $this;
    }

    /**
     * Compiles the container.
     *
     * Runs all compiler passes and freezes the container.
     *
     * @return void
     */
    public function compile(): void
    {
        if ($this->compiled) {
            return;
        }

        // Run all compiler passes
        foreach ($this->compilerPasses as $pass) {
            $pass->process($this);
        }

        // Instantiate all non-lazy services that are marked for compilation
        foreach ($this->definitions as $id => $definition) {
            if (!$definition->isLazy() && !$definition->isAbstract() && !$definition->isSynthetic()) {
                // Don't instantiate private services unless they're used
                if ($definition->isPublic()) {
                    // We'll instantiate on first get() call
                }
            }
        }

        $this->compiled = true;
    }

    /**
     * Checks if the container is compiled.
     *
     * @return bool
     */
    public function isCompiled(): bool
    {
        return $this->compiled;
    }

    /**
     * Finds all service IDs with a specific tag.
     *
     * @param string $tag
     * @return array<string, array<array<string, mixed>>> Map of service ID to tag attributes
     */
    public function findTaggedServiceIds(string $tag): array
    {
        $services = [];

        foreach ($this->definitions as $id => $definition) {
            if ($definition->hasTag($tag)) {
                $services[$id] = $definition->getTag($tag);
            }
        }

        return $services;
    }

    /**
     * {@inheritdoc}
     */
    protected function createService(string $id): mixed
    {
        // Resolve alias
        $originalId = $id;
        while ($this->hasAlias($id)) {
            $id = $this->getAlias($id);
        }

        // Get definition
        if (!$this->hasDefinition($id)) {
            throw new ServiceNotFoundException($originalId);
        }

        $definition = $this->getDefinition($id);

        // Check if synthetic
        if ($definition->isSynthetic()) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" is synthetic and must be set at runtime.', $originalId)
            );
        }

        // Check if abstract
        if ($definition->isAbstract()) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" is abstract and cannot be instantiated.', $originalId)
            );
        }

        // Create service instance
        $service = $this->instantiateService($definition);

        // Execute method calls
        foreach ($definition->getMethodCalls() as $call) {
            $method = $call['method'];
            $arguments = $this->resolveArguments($call['arguments']);
            $service->$method(...$arguments);
        }

        return $service;
    }

    /**
     * Instantiates a service from its definition.
     *
     * @param Definition $definition
     * @return mixed
     */
    private function instantiateService(Definition $definition): mixed
    {
        // Use factory if defined
        if ($factory = $definition->getFactory()) {
            return $this->callFactory($factory, $definition->getArguments());
        }

        // Get class name
        $class = $definition->getClass();
        if (!$class) {
            throw new \LogicException('Cannot instantiate service without class name.');
        }

        // Resolve arguments
        $arguments = $this->resolveArguments($definition->getArguments());

        // Instantiate
        return new $class(...$arguments);
    }

    /**
     * Calls a factory to create a service.
     *
     * @param callable|array $factory
     * @param array<mixed> $arguments
     * @return mixed
     */
    private function callFactory(callable|array $factory, array $arguments): mixed
    {
        $arguments = $this->resolveArguments($arguments);

        if (is_array($factory)) {
            [$class, $method] = $factory;

            // Resolve service reference
            if ($class instanceof Reference) {
                $class = $this->get($class->getId());
            } elseif (is_string($class) && $this->has($class)) {
                $class = $this->get($class);
            }

            return $class::$method(...$arguments);
        }

        return $factory(...$arguments);
    }

    /**
     * {@inheritdoc}
     */
    protected function hasDefinition(string $id): bool
    {
        return isset($this->definitions[$id]) || isset($this->aliases[$id]);
    }

    /**
     * Autowires a service by analyzing its constructor.
     *
     * @param string $class
     * @return array<mixed> The autowired arguments
     */
    public function autowire(string $class): array
    {
        $reflectionClass = new ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return [];
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                // Cannot autowire built-in types or union types
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                } else {
                    throw new \LogicException(
                        sprintf(
                            'Cannot autowire service "%s": parameter "$%s" must have a type-hint or default value.',
                            $class,
                            $parameter->getName()
                        )
                    );
                }
                continue;
            }

            $typeName = $type->getName();

            // Try to find service by type
            if ($this->has($typeName)) {
                $arguments[] = new Reference($typeName);
            } elseif ($this->hasAlias($typeName)) {
                $arguments[] = new Reference($this->getAlias($typeName));
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($type->allowsNull()) {
                $arguments[] = null;
            } else {
                throw new \LogicException(
                    sprintf(
                        'Cannot autowire service "%s": parameter "$%s" references class "%s" but no such service exists.',
                        $class,
                        $parameter->getName(),
                        $typeName
                    )
                );
            }
        }

        return $arguments;
    }

    /**
     * Ensures the container is not frozen.
     *
     * @return void
     * @throws FrozenContainerException
     */
    private function ensureNotFrozen(): void
    {
        if ($this->compiled) {
            throw new FrozenContainerException();
        }
    }
}
