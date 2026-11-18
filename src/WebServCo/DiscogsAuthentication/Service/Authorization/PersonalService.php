<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service\Authorization;

use Override;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Contract\AuthorizationServiceInterface;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\PersonalSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;

use function sprintf;

/**
 * A personal access token: requests on behalf of the token's holder only.
 */
final readonly class PersonalService implements AuthorizationServiceInterface
{
    public function __construct(private PersonalSettings $settings)
    {
    }

    #[Override]
    public function createAuthorizationHeader(): StringString
    {
        return new StringString(
            AuthorizationType::HEADER_NAME,
            AuthorizationType::Discogs->createHeaderValue(sprintf('token=%s', $this->settings->personalAccessToken)),
        );
    }
}
