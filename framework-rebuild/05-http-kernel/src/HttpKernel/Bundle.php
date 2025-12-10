<?php

namespace Framework\HttpKernel;

/**
 * Bundle - Base bundle implementation
 */
abstract class Bundle implements BundleInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        // Override in child class if needed
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        // Override in child class if needed
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return static::class;
    }
}
