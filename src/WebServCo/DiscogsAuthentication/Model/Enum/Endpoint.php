<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\Enum;

use Fig\Http\Message\RequestMethodInterface;

/**
 * The Discogs API endpoints of the OAuth handshake, relative to the API URL.
 */
enum Endpoint: string
{
    case AccessToken = 'oauth/access_token';

    case Identity = 'oauth/identity';

    case RequestToken = 'oauth/request_token';

    public function getMethod(): string
    {
        return $this === self::AccessToken
            ? RequestMethodInterface::METHOD_POST
            : RequestMethodInterface::METHOD_GET;
    }

    /**
     * The token endpoints take `Content-Type: application/x-www-form-urlencoded`, also on a GET without a body, as
     * the Discogs documentation shows it.
     */
    public function isTokenEndpoint(): bool
    {
        return $this !== self::Identity;
    }
}
