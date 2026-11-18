<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Contract;

use WebServCo\Data\DataTransferObject\KeyValue\StringString;

interface AuthorizationServiceInterface
{
    /**
     * The `Authorization` header of a Discogs API request: its name (key) and value.
     */
    public function createAuthorizationHeader(): StringString;
}
