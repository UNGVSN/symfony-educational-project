<?php

namespace Framework\Security\Http\Authenticator;

use Framework\Security\Core\Authentication\Token\TokenInterface;
use Framework\Security\Core\Exception\AuthenticationException;
use Framework\Security\Http\Authenticator\Passport\Passport;

/**
 * Interface for HTTP authenticators.
 *
 * Authenticators handle the authentication process for HTTP requests.
 * Each authenticator implements a specific authentication mechanism
 * (form login, JSON login, API key, etc.).
 */
interface AuthenticatorInterface
{
    /**
     * Checks if this authenticator supports the given request.
     *
     * This method is called on every request to determine if this
     * authenticator should handle authentication.
     *
     * @param Request $request The current request
     *
     * @return bool True if this authenticator supports the request
     */
    public function supports(Request $request): bool;

    /**
     * Authenticates the request and returns a passport.
     *
     * A passport contains the credentials and user information needed
     * to complete authentication.
     *
     * @param Request $request The current request
     *
     * @return Passport The authentication passport
     *
     * @throws AuthenticationException If authentication fails
     */
    public function authenticate(Request $request): Passport;

    /**
     * Creates a security token from an authenticated passport.
     *
     * @param Passport $passport The authenticated passport
     * @param string $firewallName The firewall name
     *
     * @return TokenInterface The security token
     */
    public function createToken(Passport $passport, string $firewallName): TokenInterface;

    /**
     * Called when authentication succeeds.
     *
     * This method can return a Response to redirect the user, or null
     * to continue with the request.
     *
     * @param Request $request The current request
     * @param TokenInterface $token The authenticated token
     * @param string $firewallName The firewall name
     *
     * @return Response|null A response or null
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response;

    /**
     * Called when authentication fails.
     *
     * This method should return a Response explaining the failure.
     *
     * @param Request $request The current request
     * @param AuthenticationException $exception The authentication exception
     *
     * @return Response The failure response
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response;
}

/**
 * Simple Request class for the authenticator.
 * In a real implementation, this would come from the HTTP Foundation component.
 */
class Request
{
    public function __construct(
        private array $query = [],
        private array $request = [],
        private array $server = [],
        private array $cookies = [],
        private array $files = [],
        private ?string $content = null
    ) {
    }

    public static function createFromGlobals(): self
    {
        return new self(
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIES,
            $_FILES,
            file_get_contents('php://input')
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function getPathInfo(): string
    {
        return $this->server['PATH_INFO'] ?? '/';
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function headers(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
        }
        return $headers;
    }
}

/**
 * Simple Response class for the authenticator.
 * In a real implementation, this would come from the HTTP Foundation component.
 */
class Response
{
    public function __construct(
        private string $content = '',
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }
}
