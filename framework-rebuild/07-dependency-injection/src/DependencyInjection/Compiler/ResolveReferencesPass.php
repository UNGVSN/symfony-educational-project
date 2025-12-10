<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\DependencyInjection\ContainerBuilder;
use App\DependencyInjection\Reference;

/**
 * Resolves and validates service references.
 *
 * This pass ensures all referenced services exist in the container.
 */
class ResolveReferencesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            // Skip abstract definitions
            if ($definition->isAbstract()) {
                continue;
            }

            // Validate constructor arguments
            $this->validateArguments($container, $id, $definition->getArguments());

            // Validate method call arguments
            foreach ($definition->getMethodCalls() as $call) {
                $this->validateArguments($container, $id, $call['arguments']);
            }

            // Validate factory
            if ($factory = $definition->getFactory()) {
                if (is_array($factory) && $factory[0] instanceof Reference) {
                    $this->validateReference($container, $id, $factory[0]);
                }
            }
        }
    }

    /**
     * Validates an array of arguments.
     *
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param array<mixed> $arguments
     * @return void
     */
    private function validateArguments(ContainerBuilder $container, string $serviceId, array $arguments): void
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof Reference) {
                $this->validateReference($container, $serviceId, $argument);
            } elseif (is_array($argument)) {
                $this->validateArguments($container, $serviceId, $argument);
            }
        }
    }

    /**
     * Validates a service reference.
     *
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param Reference $reference
     * @return void
     */
    private function validateReference(ContainerBuilder $container, string $serviceId, Reference $reference): void
    {
        $referencedId = $reference->getId();

        // Check if referenced service exists
        if (!$container->has($referencedId) && !$container->hasAlias($referencedId)) {
            // Handle based on invalid behavior
            if ($reference->getInvalidBehavior() === Reference::EXCEPTION_ON_INVALID_REFERENCE) {
                throw new \RuntimeException(
                    sprintf(
                        'Service "%s" has a dependency on non-existent service "%s".',
                        $serviceId,
                        $referencedId
                    )
                );
            }
        }
    }
}
