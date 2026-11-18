<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service\Authorization;

use Override;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Contract\AuthorizationServiceInterface;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;

use function sprintf;

/**
 * The consumer key and secret: no user data, a higher rate limit than an unauthenticated request.
 */
final readonly class ConsumerService implements AuthorizationServiceInterface
{
    public function __construct(private ConsumerSettings $settings)
    {
    }

    #[Override]
    public function createAuthorizationHeader(): StringString
    {
        return new StringString(
            AuthorizationType::HEADER_NAME,
            AuthorizationType::Discogs->createHeaderValue(
                sprintf('key=%s, secret=%s', $this->settings->consumerKey, $this->settings->consumerSecret),
            ),
        );
    }
}
