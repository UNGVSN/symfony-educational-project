<?php

namespace Framework\Security\Http\Authenticator;

use Framework\Security\Core\Authentication\Token\TokenInterface;
use Framework\Security\Core\Authentication\Token\UsernamePasswordToken;
use Framework\Security\Core\Exception\AuthenticationException;
use Framework\Security\Core\Exception\BadCredentialsException;
use Framework\Security\Core\User\UserProviderInterface;
use Framework\Security\Http\Authenticator\Passport\Passport;

/**
 * Form login authenticator.
 *
 * This authenticator handles traditional username/password form login.
 * It extracts credentials from POST data and verifies them.
 */
class FormLoginAuthenticator implements AuthenticatorInterface
{
    /**
     * @param UserProviderInterface $userProvider The user provider
     * @param string $loginPath The login path (e.g., '/login')
     * @param string $successPath The success redirect path (e.g., '/dashboard')
     * @param string $usernameParameter The username field name (default: '_username')
     * @param string $passwordParameter The password field name (default: '_password')
     */
    public function __construct(
        private UserProviderInterface $userProvider,
        private string $loginPath = '/login',
        private string $successPath = '/',
        private string $usernameParameter = '_username',
        private string $passwordParameter = '_password'
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): bool
    {
        // Support POST requests to the login path
        return $request->isMethod('POST') && $request->getPathInfo() === $this->loginPath;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(Request $request): Passport
    {
        $username = $request->get($this->usernameParameter);
        $password = $request->get($this->passwordParameter);

        if (!$username || !$password) {
            throw new BadCredentialsException('Username and password are required.');
        }

        return new Passport(
            userIdentifier: $username,
            password: $password
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        // Load the user
        $user = $this->userProvider->loadUserByIdentifier($passport->getUserIdentifier());

        // Verify password
        if (!password_verify($passport->getPassword(), $user->getPassword())) {
            throw new BadCredentialsException('Invalid password.');
        }

        // Check if password needs rehashing
        if (password_needs_rehash($user->getPassword(), PASSWORD_DEFAULT)) {
            // In a real app, you would update the password hash here
            // $this->updatePasswordHash($user, $passport->getPassword());
        }

        // Create and return token
        return UsernamePasswordToken::fromUser($user, $firewallName);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Redirect to success path
        return new Response(
            content: sprintf(
                '<html><head><meta http-equiv="refresh" content="0;url=%s"></head></html>',
                $this->successPath
            ),
            status: 302,
            headers: ['Location' => $this->successPath]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): Response {
        // Return error response
        return new Response(
            content: sprintf(
                '<html><body><h1>Login Failed</h1><p>%s</p><a href="%s">Try again</a></body></html>',
                htmlspecialchars($exception->getMessage()),
                $this->loginPath
            ),
            status: 401
        );
    }

    /**
     * Get the login path.
     *
     * @return string
     */
    public function getLoginPath(): string
    {
        return $this->loginPath;
    }

    /**
     * Get the success path.
     *
     * @return string
     */
    public function getSuccessPath(): string
    {
        return $this->successPath;
    }
}
