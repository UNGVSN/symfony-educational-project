<?php

namespace Framework\HttpKernel;

/**
 * BundleInterface
 *
 * Bundles are reusable packages that extend the framework.
 * They can:
 * - Register services
 * - Provide configuration
 * - Add event listeners
 * - Contribute compiler passes
 */
interface BundleInterface
{
    /**
     * Boots the bundle.
     */
    public function boot(): void;

    /**
     * Shuts down the bundle.
     */
    public function shutdown(): void;

    /**
     * Returns the bundle name.
     */
    public function getName(): string;
}
