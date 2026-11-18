<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service\Authorization;

use Override;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Contract\AuthorizationServiceInterface;
use WebServCo\DiscogsAuthentication\Factory\OAuthHeaderFactory;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\OAuthSettings;

/**
 * A user's access token: requests on behalf of any user who authorized the application. A new nonce and timestamp
 * for each header.
 */
final readonly class OAuthService implements AuthorizationServiceInterface
{
    public function __construct(
        private OAuthSettings $settings,
        private OAuthHeaderFactory $oauthHeaderFactory = new OAuthHeaderFactory(),
    ) {
    }

    /**
     * @throws \InvalidArgumentException A value the header can't carry unchanged (`HeaderValueValidator`).
     */
    #[Override]
    public function createAuthorizationHeader(): StringString
    {
        return $this->oauthHeaderFactory->createHeader(
            new ConsumerSettings($this->settings->consumerKey, $this->settings->consumerSecret),
            $this->settings->oauthToken,
            $this->settings->oauthTokenSecret,
        );
    }
}
