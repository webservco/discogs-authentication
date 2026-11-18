<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Contract;

use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;

/**
 * Reads the query of the callback URL, where Discogs sends the user back after the authorize page.
 *
 * The query parameters are mixed (`\Psr\Http\Message\ServerRequestInterface::getQueryParams()`); each value is read
 * with a strict type.
 */
interface CallbackReaderInterface
{
    /**
     * The user refused to authorize the application: the query has `denied`, and no verifier.
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $queryParams
     * @phpcs:enable
     */
    public function isDenied(array $queryParams): bool;

    /**
     * The verifier, after checking that the callback is for this request token.
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $queryParams
     * @phpcs:enable
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    public function readVerifier(array $queryParams, RequestToken $requestToken): string;
}
