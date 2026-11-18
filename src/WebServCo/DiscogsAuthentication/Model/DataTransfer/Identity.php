<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * The user an access token belongs to, from `oauth/identity`. The ID is the key: the user can change the username on
 * Discogs.
 */
final readonly class Identity implements DataTransferInterface
{
    public function __construct(
        public int $id,
        public string $username,
        // eg. `https://api.discogs.com/users/{username}`
        public string $resourceUrl,
        // The name of the Discogs application the token was issued to.
        public string $consumerName,
    ) {
    }
}
