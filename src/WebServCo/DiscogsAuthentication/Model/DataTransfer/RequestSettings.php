<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final readonly class RequestSettings implements DataTransferInterface
{
    public const string API_URL = 'https://api.discogs.com/';

    public function __construct(
        // Discogs asks every client for a User-Agent that names it, eg. 'ExampleApplication/1.0 +https://example.com'.
        public string $userAgent,
        // The endpoints are relative to it. The trailing slash is optional.
        public string $apiUrl = self::API_URL,
    ) {
    }
}
