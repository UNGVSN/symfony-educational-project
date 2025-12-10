<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass interface.
 *
 * Compiler passes process service definitions during container compilation.
 * They can modify, add, or remove service definitions.
 */
interface CompilerPassInterface
{
    /**
     * Processes the container builder.
     *
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container): void;
}
