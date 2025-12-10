<?php

declare(strict_types=1);

namespace App\Form;

use App\Http\Request;

/**
 * Form is the core implementation of the FormInterface.
 *
 * It handles:
 * - Request data extraction and binding
 * - Child form management (form tree)
 * - Validation
 * - View creation
 *
 * Forms can be nested to create complex structures.
 */
class Form implements FormInterface
{
    /**
     * Form children (nested forms).
     *
     * @var array<string, FormInterface>
     */
    private array $children = [];

    /**
     * Validation errors.
     *
     * @var array<FormError>
     */
    private array $errors = [];

    /**
     * Whether the form was submitted.
     */
    private bool $submitted = false;

    /**
     * The form data (can be object or array).
     */
    private mixed $data = null;

    /**
     * Parent form (if this is a child form).
     */
    private ?FormInterface $parent = null;

    /**
     * Creates a new Form.
     *
     * @param string $name Form name (used for HTML name attribute)
     * @param array $options Form options (required, disabled, attr, etc.)
     */
    public function __construct(
        private readonly string $name,
        private readonly array $options = []
    ) {
    }

    /**
     * Handles the HTTP request and binds data to the form.
     */
    public function handleRequest(Request $request): self
    {
        // Only handle if not already submitted
        if ($this->submitted) {
            return $this;
        }

        // Check if request method matches
        $method = $this->getOption('method', 'POST');
        if ($request->getMethod() !== strtoupper($method)) {
            return $this;
        }

        // Extract data from request
        $data = $method === 'GET'
            ? $request->query->all()
            : $request->request->all();

        // If form has a name, extract its data
        if ($this->name !== '') {
            $data = $data[$this->name] ?? [];
        }

        // Submit the form with extracted data
        $this->submit($data);

        return $this;
    }

    /**
     * Submits data to the form.
     *
     * This is called internally by handleRequest() or can be called manually
     * for programmatic form submission (e.g., in tests or API endpoints).
     */
    public function submit(mixed $data): self
    {
        $this->submitted = true;

        // Set data to children
        if (is_array($data)) {
            foreach ($this->children as $name => $child) {
                if (isset($data[$name])) {
                    $child->submit($data[$name]);
                }
            }
        }

        // Set data to this form
        $this->setData($data);

        return $this;
    }

    /**
     * Checks if the form was submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * Checks if the form is valid.
     */
    public function isValid(): bool
    {
        if (!$this->submitted) {
            return false;
        }

        // Check if this form has errors
        if (count($this->errors) > 0) {
            return false;
        }

        // Check if any child has errors
        foreach ($this->children as $child) {
            if (!$child->isValid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the form data.
     */
    public function getData(): mixed
    {
        // If we have a data class, create/populate an object
        $dataClass = $this->getOption('data_class');
        if ($dataClass && class_exists($dataClass)) {
            return $this->mapDataToObject($dataClass);
        }

        // Otherwise return array or scalar value
        if (count($this->children) > 0) {
            return $this->mapDataToArray();
        }

        return $this->data;
    }

    /**
     * Maps form data to an object instance.
     */
    private function mapDataToObject(string $class): object
    {
        $object = is_object($this->data) && $this->data instanceof $class
            ? $this->data
            : new $class();

        foreach ($this->children as $name => $child) {
            $setter = 'set' . ucfirst($name);
            if (method_exists($object, $setter)) {
                $object->$setter($child->getData());
            }
        }

        return $object;
    }

    /**
     * Maps form data to an associative array.
     */
    private function mapDataToArray(): array
    {
        $data = [];

        foreach ($this->children as $name => $child) {
            $data[$name] = $child->getData();
        }

        return $data;
    }

    /**
     * Sets the form data.
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;

        // If data is an object, extract values for children
        if (is_object($data)) {
            foreach ($this->children as $name => $child) {
                $getter = 'get' . ucfirst($name);
                if (method_exists($data, $getter)) {
                    $child->setData($data->$getter());
                }
            }
        } elseif (is_array($data)) {
            foreach ($this->children as $name => $child) {
                if (isset($data[$name])) {
                    $child->setData($data[$name]);
                }
            }
        }

        return $this;
    }

    /**
     * Creates a view representation of the form.
     */
    public function createView(): FormView
    {
        $view = new FormView();

        // Set basic variables
        $view->vars = [
            'value' => $this->getViewData(),
            'name' => $this->getFullName(),
            'id' => $this->createId(),
            'required' => $this->getOption('required', false),
            'disabled' => $this->getOption('disabled', false),
            'label' => $this->getOption('label', $this->formatLabel($this->name)),
            'attr' => $this->getOption('attr', []),
            'errors' => $this->errors,
            'valid' => $this->isValid(),
            'submitted' => $this->submitted,
        ];

        // Add children views
        foreach ($this->children as $name => $child) {
            $childView = $child->createView();
            $view->addChild($name, $childView);
        }

        return $view;
    }

    /**
     * Gets the data for view rendering.
     */
    private function getViewData(): mixed
    {
        if (count($this->children) > 0) {
            return null; // Children will have their own values
        }

        return $this->data ?? '';
    }

    /**
     * Creates an HTML ID for the form field.
     */
    private function createId(): string
    {
        $parts = [];

        // Add parent names
        $parent = $this->parent;
        while ($parent !== null) {
            if ($parent->getName() !== '') {
                array_unshift($parts, $parent->getName());
            }
            $parent = $parent instanceof self ? $parent->parent : null;
        }

        // Add this form's name
        if ($this->name !== '') {
            $parts[] = $this->name;
        }

        return implode('_', $parts);
    }

    /**
     * Gets the full HTML name attribute.
     */
    private function getFullName(): string
    {
        if ($this->parent === null) {
            return $this->name;
        }

        $parentName = $this->parent instanceof self
            ? $this->parent->getFullName()
            : $this->parent->getName();

        if ($parentName === '') {
            return $this->name;
        }

        return $this->name !== ''
            ? $parentName . '[' . $this->name . ']'
            : $parentName;
    }

    /**
     * Formats a field name into a human-readable label.
     */
    private function formatLabel(string $name): string
    {
        // Convert camelCase or snake_case to Title Case
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        $label = str_replace('_', ' ', $label);
        return ucwords($label);
    }

    /**
     * Returns the form name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Adds a child form.
     */
    public function add(FormInterface $child): self
    {
        $this->children[$child->getName()] = $child;

        if ($child instanceof self) {
            $child->parent = $this;
        }

        return $this;
    }

    /**
     * Gets a child form by name.
     */
    public function get(string $name): ?FormInterface
    {
        return $this->children[$name] ?? null;
    }

    /**
     * Checks if the form has a child with the given name.
     */
    public function has(string $name): bool
    {
        return isset($this->children[$name]);
    }

    /**
     * Returns all child forms.
     */
    public function all(): array
    {
        return $this->children;
    }

    /**
     * Returns validation errors for this form.
     */
    public function getErrors(bool $deep = false): array
    {
        if (!$deep) {
            return $this->errors;
        }

        $errors = $this->errors;

        foreach ($this->children as $child) {
            $errors = array_merge($errors, $child->getErrors(true));
        }

        return $errors;
    }

    /**
     * Adds a validation error to the form.
     */
    public function addError(FormError $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    /**
     * Returns the form configuration options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Returns a specific option value.
     */
    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
}
