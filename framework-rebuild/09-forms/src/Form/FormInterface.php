<?php

declare(strict_types=1);

namespace App\Form;

use App\Http\Request;

/**
 * FormInterface defines the contract for all form objects.
 *
 * Forms handle the complete lifecycle:
 * 1. Request handling (extracting data from HTTP request)
 * 2. Data binding (mapping request data to form structure)
 * 3. Validation (checking data against constraints)
 * 4. View creation (generating presentation model for rendering)
 */
interface FormInterface
{
    /**
     * Handles the HTTP request and binds data to the form.
     *
     * This method:
     * - Checks if the request method matches (POST, GET, etc.)
     * - Extracts data from the request
     * - Binds data to form fields
     * - Triggers validation
     *
     * @param Request $request The HTTP request object
     * @return self For method chaining
     */
    public function handleRequest(Request $request): self;

    /**
     * Checks if the form was submitted.
     *
     * A form is considered submitted when:
     * - handleRequest() was called
     * - The request method matches the form method
     * - The form data is present in the request
     *
     * @return bool True if submitted, false otherwise
     */
    public function isSubmitted(): bool;

    /**
     * Checks if the form is valid.
     *
     * A form is valid when:
     * - It has been submitted
     * - All validation constraints pass
     * - All child forms are valid
     *
     * @return bool True if valid, false otherwise
     */
    public function isValid(): bool;

    /**
     * Returns the form data.
     *
     * This can be:
     * - An object with bound data (e.g., User entity)
     * - An array of values
     * - null if no data was set
     *
     * @return mixed The form data
     */
    public function getData(): mixed;

    /**
     * Sets the form data.
     *
     * This is typically called:
     * - During form creation (initial data)
     * - After handleRequest() (submitted data)
     *
     * @param mixed $data The data to set
     * @return self For method chaining
     */
    public function setData(mixed $data): self;

    /**
     * Creates a view representation of the form.
     *
     * The FormView contains all information needed for rendering:
     * - Field names and IDs
     * - Current values
     * - Validation errors
     * - HTML attributes
     *
     * @return FormView The view object
     */
    public function createView(): FormView;

    /**
     * Returns the form name.
     *
     * The name is used for:
     * - HTML name attributes (e.g., user[name])
     * - Request parameter extraction
     * - CSRF token generation
     *
     * @return string The form name
     */
    public function getName(): string;

    /**
     * Adds a child form.
     *
     * Forms can be nested to create complex structures:
     * UserForm -> AddressForm -> StreetField
     *
     * @param FormInterface $child The child form
     * @return self For method chaining
     */
    public function add(FormInterface $child): self;

    /**
     * Gets a child form by name.
     *
     * @param string $name The child name
     * @return FormInterface|null The child form or null if not found
     */
    public function get(string $name): ?FormInterface;

    /**
     * Checks if the form has a child with the given name.
     *
     * @param string $name The child name
     * @return bool True if child exists, false otherwise
     */
    public function has(string $name): bool;

    /**
     * Returns all child forms.
     *
     * @return array<string, FormInterface> Array of child forms indexed by name
     */
    public function all(): array;

    /**
     * Returns validation errors for this form.
     *
     * @param bool $deep Include errors from child forms
     * @return array<FormError> Array of errors
     */
    public function getErrors(bool $deep = false): array;

    /**
     * Adds a validation error to the form.
     *
     * @param FormError $error The error to add
     * @return self For method chaining
     */
    public function addError(FormError $error): self;

    /**
     * Returns the form configuration options.
     *
     * @return array<string, mixed> Configuration options
     */
    public function getOptions(): array;

    /**
     * Returns a specific option value.
     *
     * @param string $name The option name
     * @param mixed $default Default value if option doesn't exist
     * @return mixed The option value
     */
    public function getOption(string $name, mixed $default = null): mixed;
}
