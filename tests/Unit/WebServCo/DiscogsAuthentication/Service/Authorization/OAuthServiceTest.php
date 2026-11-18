<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service\Authorization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Factory\OAuthHeaderFactory;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\OAuthSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Service\Authorization\OAuthService;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;

#[CoversClass(OAuthService::class)]
#[UsesClass(AuthorizationType::class)]
#[UsesClass(ConsumerSettings::class)]
#[UsesClass(HeaderValueValidator::class)]
#[UsesClass(OAuthHeaderFactory::class)]
#[UsesClass(OAuthSettings::class)]
final class OAuthServiceTest extends TestCase
{
    /**
     * The same header as the one the service sent before it moved into this component, apart from the nonce's source.
     */
    public function testCredentialsGoInTheAuthorizationHeader(): void
    {
        $header = new OAuthService(
            new OAuthSettings('the-key', 'the-secret', 'the-token', 'the-token-secret'),
        )->createAuthorizationHeader();

        self::assertSame('Authorization', $header->key);
        self::assertMatchesRegularExpression(
            '/^OAuth oauth_consumer_key="the-key",oauth_nonce="\d+",oauth_token="the-token",'
            . 'oauth_signature="the-secret&the-token-secret",oauth_signature_method="PLAINTEXT",'
            . 'oauth_timestamp="\d+"$/',
            $header->value,
        );
    }
}
