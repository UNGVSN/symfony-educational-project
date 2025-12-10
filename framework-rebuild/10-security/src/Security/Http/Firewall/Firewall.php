<?php

namespace Framework\Security\Http\Firewall;

use Framework\Security\Core\Authentication\TokenStorage;
use Framework\Security\Core\Exception\AuthenticationException;
use Framework\Security\Http\Authenticator\AuthenticatorInterface;
use Framework\Security\Http\Authenticator\Request;
use Framework\Security\Http\Authenticator\Response;

/**
 * Firewall.
 *
 * The firewall is the entry point for authentication. It matches requests
 * against a pattern and triggers authenticators to handle authentication.
 */
class Firewall
{
    /**
     * @param string $pattern URL pattern to match (regex)
     * @param array<AuthenticatorInterface> $authenticators List of authenticators
     * @param TokenStorage $tokenStorage Token storage
     * @param string $name Firewall name
     */
    public function __construct(
        private string $pattern,
        private array $authenticators,
        private TokenStorage $tokenStorage,
        private string $name = 'main'
    ) {
    }

    /**
     * Handle a request.
     *
     * This method is called on every request. It checks if the firewall
     * matches the request and triggers authentication if needed.
     *
     * @param Request $request The request
     *
     * @return Response|null A response if authentication completes, null otherwise
     */
    public function handle(Request $request): ?Response
    {
        // Check if firewall matches
        if (!$this->matches($request)) {
            return null;
        }

        // If already authenticated, continue
        if ($this->tokenStorage->hasToken()) {
            return null;
        }

        // Find a supporting authenticator
        foreach ($this->authenticators as $authenticator) {
            if (!$authenticator->supports($request)) {
                continue;
            }

            try {
                // Authenticate
                $passport = $authenticator->authenticate($request);

                // Create token
                $token = $authenticator->createToken($passport, $this->name);

                // Store token
                $this->tokenStorage->setToken($token);

                // Success
                return $authenticator->onAuthenticationSuccess(
                    $request,
                    $token,
                    $this->name
                );
            } catch (AuthenticationException $e) {
                // Failure
                return $authenticator->onAuthenticationFailure($request, $e);
            }
        }

        return null;
    }

    /**
     * Check if the firewall matches the request.
     *
     * @param Request $request The request
     *
     * @return bool True if the firewall matches
     */
    private function matches(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        return (bool) preg_match('#' . $this->pattern . '#', $pathInfo);
    }

    /**
     * Get the firewall name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the pattern.
     *
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Get the authenticators.
     *
     * @return array<AuthenticatorInterface>
     */
    public function getAuthenticators(): array
    {
        return $this->authenticators;
    }

    /**
     * Add an authenticator.
     *
     * @param AuthenticatorInterface $authenticator
     *
     * @return void
     */
    public function addAuthenticator(AuthenticatorInterface $authenticator): void
    {
        $this->authenticators[] = $authenticator;
    }
}
