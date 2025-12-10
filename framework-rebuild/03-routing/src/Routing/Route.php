<?php

declare(strict_types=1);

namespace App\Routing;

/**
 * Represents a single route with pattern matching and compilation capabilities.
 *
 * A Route defines:
 * - path: URL pattern with placeholders like /article/{id}
 * - defaults: Default values for parameters (including _controller)
 * - requirements: Regex constraints for parameters
 * - methods: Allowed HTTP methods (GET, POST, etc.)
 *
 * Example:
 *   $route = new Route('/article/{id}',
 *       ['_controller' => 'ArticleController::show'],
 *       ['id' => '\d+'],
 *       ['GET']
 *   );
 */
class Route
{
    /**
     * @var string The URL pattern with placeholders (e.g., /article/{id})
     */
    private string $path;

    /**
     * @var array<string, mixed> Default values for parameters
     */
    private array $defaults;

    /**
     * @var array<string, string> Regex requirements for parameters
     */
    private array $requirements;

    /**
     * @var array<string> Allowed HTTP methods (GET, POST, etc.)
     */
    private array $methods;

    /**
     * @var string|null Compiled regex pattern (cached after first compilation)
     */
    private ?string $compiledPattern = null;

    /**
     * @var array<string>|null List of parameter names in order
     */
    private ?array $variables = null;

    /**
     * @param string $path URL pattern with placeholders
     * @param array<string, mixed> $defaults Default values for parameters
     * @param array<string, string> $requirements Regex constraints for parameters
     * @param array<string> $methods Allowed HTTP methods
     */
    public function __construct(
        string $path,
        array $defaults = [],
        array $requirements = [],
        array $methods = []
    ) {
        $this->path = $path;
        $this->defaults = $defaults;
        $this->requirements = $requirements;
        $this->methods = array_map('strtoupper', $methods);
    }

    /**
     * Get the route path pattern.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get default values.
     *
     * @return array<string, mixed>
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Get parameter requirements.
     *
     * @return array<string, string>
     */
    public function getRequirements(): array
    {
        return $this->requirements;
    }

    /**
     * Get allowed HTTP methods.
     *
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the list of parameter names in the route.
     *
     * @return array<string>
     */
    public function getVariables(): array
    {
        if ($this->variables === null) {
            $this->compile();
        }

        return $this->variables;
    }

    /**
     * Match a path against this route.
     *
     * Returns an array of matched parameters on success, or false if no match.
     * The matched parameters include extracted placeholder values merged with defaults.
     *
     * @param string $pathInfo The path to match (e.g., /article/42)
     * @param string $method The HTTP method (e.g., GET, POST)
     * @return array<string, mixed>|false Array of parameters or false
     */
    public function match(string $pathInfo, string $method = 'GET'): array|false
    {
        // Check HTTP method if methods are restricted
        if (!empty($this->methods) && !in_array(strtoupper($method), $this->methods, true)) {
            return false;
        }

        // Compile the route pattern if not already done
        $pattern = $this->compile();

        // Try to match the path
        if (!preg_match($pattern, $pathInfo, $matches)) {
            return false;
        }

        // Extract named parameters
        $parameters = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = $value;
            }
        }

        // Merge with defaults (defaults don't override matched values)
        return array_merge($this->defaults, $parameters);
    }

    /**
     * Compile the route pattern into a regex.
     *
     * This converts a route like /article/{id}/{slug} into a regex pattern
     * that can match paths and extract parameters.
     *
     * Process:
     * 1. Find all placeholders: {id}, {slug}, etc.
     * 2. Replace each with a named capture group
     * 3. Apply requirements (regex constraints) if specified
     * 4. Handle optional parameters (those with defaults)
     *
     * @return string The compiled regex pattern
     */
    public function compile(): string
    {
        if ($this->compiledPattern !== null) {
            return $this->compiledPattern;
        }

        $pattern = $this->path;
        $this->variables = [];

        // Find all placeholders: {name}
        if (preg_match_all('#{([a-zA-Z_][a-zA-Z0-9_]*)}#', $pattern, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $variable = $match[1];
                $this->variables[] = $variable;

                // Get requirement (constraint) for this parameter
                $requirement = $this->requirements[$variable] ?? '[^/]+';

                // Determine if this parameter is optional (has a default value)
                $isOptional = array_key_exists($variable, $this->defaults);

                // Build the regex replacement
                if ($isOptional) {
                    // Optional parameter: the entire segment is optional
                    // /blog/{page} with default page=1 becomes /blog(?:/(?P<page>\d+))?
                    $replacement = sprintf('(?:/(?P<%s>%s))?', $variable, $requirement);
                } else {
                    // Required parameter
                    $replacement = sprintf('(?P<%s>%s)', $variable, $requirement);
                }

                // Replace the placeholder with the regex pattern
                $pattern = str_replace($match[0], $replacement, $pattern);
            }
        }

        // Wrap in delimiters and anchors
        $this->compiledPattern = '#^' . $pattern . '$#';

        return $this->compiledPattern;
    }

    /**
     * Get the compiled regex pattern.
     *
     * @return string|null
     */
    public function getCompiledPattern(): ?string
    {
        return $this->compiledPattern;
    }

    /**
     * Check if a specific HTTP method is allowed.
     */
    public function supportsMethod(string $method): bool
    {
        if (empty($this->methods)) {
            return true; // All methods allowed
        }

        return in_array(strtoupper($method), $this->methods, true);
    }

    /**
     * Get default value for a parameter.
     *
     * @param string $name Parameter name
     * @return mixed|null
     */
    public function getDefault(string $name): mixed
    {
        return $this->defaults[$name] ?? null;
    }

    /**
     * Check if a parameter has a default value.
     */
    public function hasDefault(string $name): bool
    {
        return array_key_exists($name, $this->defaults);
    }

    /**
     * Get requirement for a parameter.
     */
    public function getRequirement(string $name): ?string
    {
        return $this->requirements[$name] ?? null;
    }

    /**
     * Set the path pattern.
     *
     * @return $this
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        $this->compiledPattern = null; // Reset compilation
        $this->variables = null;
        return $this;
    }

    /**
     * Set defaults.
     *
     * @param array<string, mixed> $defaults
     * @return $this
     */
    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        $this->compiledPattern = null; // Reset compilation
        return $this;
    }

    /**
     * Add a default value.
     *
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return $this
     */
    public function addDefaults(string $name, mixed $default): self
    {
        $this->defaults[$name] = $default;
        $this->compiledPattern = null; // Reset compilation
        return $this;
    }

    /**
     * Set requirements.
     *
     * @param array<string, string> $requirements
     * @return $this
     */
    public function setRequirements(array $requirements): self
    {
        $this->requirements = $requirements;
        $this->compiledPattern = null; // Reset compilation
        return $this;
    }

    /**
     * Add a requirement.
     *
     * @return $this
     */
    public function addRequirement(string $name, string $regex): self
    {
        $this->requirements[$name] = $regex;
        $this->compiledPattern = null; // Reset compilation
        return $this;
    }

    /**
     * Set HTTP methods.
     *
     * @param array<string> $methods
     * @return $this
     */
    public function setMethods(array $methods): self
    {
        $this->methods = array_map('strtoupper', $methods);
        return $this;
    }
}
