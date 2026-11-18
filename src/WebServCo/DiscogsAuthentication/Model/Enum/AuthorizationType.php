<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\Enum;

use function sprintf;

/**
 * The scheme at the start of the `Authorization` header's value, as the Discogs documentation shows it:
 * `Authorization: Discogs key=…, secret=…`, `Authorization: Discogs token=…`, `Authorization: OAuth oauth_…`.
 *
 * Discogs reads credentials only from that header: a header named after the scheme (`Discogs: key=…`) is ignored,
 * and the request is unauthenticated.
 */
enum AuthorizationType: string
{
    /**
     * Consumer key and secret (general access only), or a personal access token (full access for its holder only).
     */
    case Discogs = 'Discogs';

    /**
     * OAuth 1.0a with an access token and secret (full access on behalf of any user who authorized the application).
     */
    case OAuth = 'OAuth';

    public const string HEADER_NAME = 'Authorization';

    /**
     * The header value: the scheme, a space, then the credentials.
     */
    public function createHeaderValue(string $credentials): string
    {
        return sprintf('%s %s', $this->value, $credentials);
    }
}
