<?php

declare(strict_types=1);

namespace App\Form;

/**
 * FormView is the presentation model for rendering forms.
 *
 * It contains all the information needed to render a form in templates:
 * - Field values
 * - HTML attributes (name, id, class, etc.)
 * - Validation errors
 * - Labels and help text
 * - Child form views
 *
 * This separates the form logic from rendering concerns.
 */
class FormView implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * Variables available for rendering.
     *
     * Common variables:
     * - value: The field value
     * - name: HTML name attribute
     * - id: HTML id attribute
     * - required: Is field required?
     * - disabled: Is field disabled?
     * - label: Field label
     * - attr: Additional HTML attributes
     * - errors: Validation errors
     */
    public array $vars = [];

    /**
     * Child form views (for nested forms).
     *
     * @var array<string, FormView>
     */
    public array $children = [];

    /**
     * Parent form view.
     */
    public ?FormView $parent = null;

    /**
     * Creates a new FormView.
     *
     * @param array $vars Initial variables
     */
    public function __construct(array $vars = [])
    {
        $this->vars = $vars;
    }

    /**
     * Checks if a variable exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->vars[$offset]);
    }

    /**
     * Gets a variable value.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->vars[$offset] ?? null;
    }

    /**
     * Sets a variable value.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->vars[$offset] = $value;
    }

    /**
     * Unsets a variable.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->vars[$offset]);
    }

    /**
     * Returns an iterator over child views.
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * Returns the number of child views.
     */
    public function count(): int
    {
        return count($this->children);
    }

    /**
     * Adds a child view.
     */
    public function addChild(string $name, FormView $child): void
    {
        $this->children[$name] = $child;
        $child->parent = $this;
    }

    /**
     * Gets a child view by name.
     */
    public function getChild(string $name): ?FormView
    {
        return $this->children[$name] ?? null;
    }

    /**
     * Checks if a child view exists.
     */
    public function hasChild(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * Returns all child views.
     *
     * @return array<string, FormView>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Sets a view variable.
     */
    public function setVar(string $name, mixed $value): void
    {
        $this->vars[$name] = $value;
    }

    /**
     * Gets a view variable.
     */
    public function getVar(string $name, mixed $default = null): mixed
    {
        return $this->vars[$name] ?? $default;
    }

    /**
     * Checks if a view variable exists.
     */
    public function hasVar(string $name): bool
    {
        return isset($this->vars[$name]);
    }
}
