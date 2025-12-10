<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Reference;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Autowiring compiler pass.
 *
 * Automatically resolves service dependencies by analyzing type hints.
 */
class AutowirePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // First pass: register services by their interface/parent class
        $this->registerTypeAliases($container);

        // Second pass: autowire services that need it
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!$definition->isAutowired() || $definition->isAbstract()) {
                continue;
            }

            $class = $definition->getClass();
            if (!$class || !class_exists($class)) {
                continue;
            }

            // Only autowire if no arguments are already set
            if (empty($definition->getArguments())) {
                $arguments = $this->autowireConstructor($container, $class);
                $definition->setArguments($arguments);
            }
        }
    }

    /**
     * Registers type aliases for autowiring.
     *
     * Maps interfaces and parent classes to their implementations.
     *
     * @param ContainerBuilder $container
     * @return void
     */
    private function registerTypeAliases(ContainerBuilder $container): void
    {
        $typeMap = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || !$definition->isPublic()) {
                continue;
            }

            $class = $definition->getClass();
            if (!$class || !class_exists($class) && !interface_exists($class)) {
                continue;
            }

            // Register by class name
            if (!isset($typeMap[$class])) {
                $typeMap[$class] = $id;
            }

            // Register by interfaces
            try {
                $reflectionClass = new ReflectionClass($class);

                foreach ($reflectionClass->getInterfaceNames() as $interface) {
                    if (!isset($typeMap[$interface])) {
                        $typeMap[$interface] = $id;
                    }
                }

                // Register by parent class
                $parent = $reflectionClass->getParentClass();
                if ($parent && !isset($typeMap[$parent->getName()])) {
                    $typeMap[$parent->getName()] = $id;
                }
            } catch (\ReflectionException $e) {
                // Skip if class cannot be reflected
                continue;
            }
        }

        // Set aliases for autowiring
        foreach ($typeMap as $type => $serviceId) {
            if (!$container->hasAlias($type) && !$container->hasDefinition($type)) {
                $container->setAlias($type, $serviceId);
            }
        }
    }

    /**
     * Autowires a constructor by analyzing parameter type hints.
     *
     * @param ContainerBuilder $container
     * @param string $class
     * @return array<mixed>
     */
    private function autowireConstructor(ContainerBuilder $container, string $class): array
    {
        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return [];
        }

        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return [];
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            // Skip if no type hint or built-in type
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \LogicException(
                    sprintf(
                        'Cannot autowire service "%s": parameter "$%s" must have a type-hint or default value.',
                        $class,
                        $parameter->getName()
                    )
                );
            }

            $typeName = $type->getName();

            // Try to find service by type
            $serviceId = $this->findServiceByType($container, $typeName);

            if ($serviceId !== null) {
                $arguments[] = new Reference($serviceId);
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
     * Finds a service by its type.
     *
     * @param ContainerBuilder $container
     * @param string $type
     * @return string|null
     */
    private function findServiceByType(ContainerBuilder $container, string $type): ?string
    {
        // Check if service exists with this ID
        if ($container->hasDefinition($type)) {
            return $type;
        }

        // Check if alias exists
        if ($container->hasAlias($type)) {
            return $container->getAlias($type);
        }

        // Find by class/interface match
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if (!$class) {
                continue;
            }

            if ($class === $type) {
                return $id;
            }

            // Check if class implements interface or extends parent
            if (class_exists($class) || interface_exists($class)) {
                if (is_subclass_of($class, $type)) {
                    return $id;
                }
            }
        }

        return null;
    }
}
