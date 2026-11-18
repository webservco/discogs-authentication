<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service;

use Override;
use WebServCo\DiscogsAuthentication\Contract\CallbackReaderInterface;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;

use function array_key_exists;
use function hash_equals;
use function is_string;

/**
 * Discogs sends the user back with `oauth_token` (the request token) and `oauth_verifier`, or, when the user refuses,
 * with only `denied` (the request token).
 */
final readonly class CallbackReader implements CallbackReaderInterface
{
    public function __construct(private HeaderValueValidator $headerValueValidator = new HeaderValueValidator())
    {
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $queryParams
     * @phpcs:enable
     */
    #[Override]
    public function isDenied(array $queryParams): bool
    {
        return array_key_exists('denied', $queryParams);
    }

    /**
     * The token must be the one of this login, so a callback URL from someone else's login is refused.
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $queryParams
     * @phpcs:enable
     */
    #[Override]
    public function readVerifier(array $queryParams, RequestToken $requestToken): string
    {
        $token = $queryParams['oauth_token'] ?? null;
        if (!is_string($token) || !hash_equals($requestToken->token, $token)) {
            throw new AuthenticationException('Callback: oauth_token is not the request token of this login.');
        }

        $verifier = $queryParams['oauth_verifier'] ?? null;
        if (!is_string($verifier) || !$this->headerValueValidator->isValid($verifier)) {
            throw new AuthenticationException('Callback: oauth_verifier is missing or not valid.');
        }

        return $verifier;
    }
}
