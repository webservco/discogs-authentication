<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Factory\OAuthHeaderFactory;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;

use function preg_match;
use function time;

#[CoversClass(OAuthHeaderFactory::class)]
#[UsesClass(AuthorizationType::class)]
#[UsesClass(ConsumerSettings::class)]
#[UsesClass(HeaderValueValidator::class)]
final class OAuthHeaderFactoryTest extends TestCase
{
    public function testRequestTokenHeaderHasNoTokenAndAnEmptyTokenSecret(): void
    {
        $header = new OAuthHeaderFactory()->createHeader(
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
            null,
            '',
            ['oauth_callback' => 'https://example.com/account/callback?a=1&b=2'],
        );

        self::assertSame('Authorization', $header->key);
        self::assertMatchesRegularExpression(
            '/^OAuth oauth_consumer_key="ConsumerKey",oauth_nonce="\d+",oauth_signature="ConsumerSecret&",'
            . 'oauth_signature_method="PLAINTEXT",oauth_timestamp="\d+",'
            . 'oauth_callback="https:\/\/example\.com\/account\/callback\?a=1&b=2"$/',
            $header->value,
        );
    }

    public function testTokenComesBeforeTheSignatureAndExtraParametersLast(): void
    {
        $header = new OAuthHeaderFactory()->createHeader(
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
            'Token',
            'TokenSecret',
            ['oauth_verifier' => 'Verifier'],
        );

        self::assertMatchesRegularExpression(
            '/^OAuth oauth_consumer_key="ConsumerKey",oauth_nonce="\d+",oauth_token="Token",'
            . 'oauth_signature="ConsumerSecret&TokenSecret",oauth_signature_method="PLAINTEXT",'
            . 'oauth_timestamp="\d+",oauth_verifier="Verifier"$/',
            $header->value,
        );
    }

    public function testNonceIsNewAndTimestampIsNow(): void
    {
        $before = time();
        $first = $this->readNonceAndTimestamp();
        $second = $this->readNonceAndTimestamp();
        $after = time();

        self::assertNotSame($first[0], $second[0]);
        self::assertGreaterThanOrEqual($before, $first[1]);
        self::assertLessThanOrEqual($after, $second[1]);
    }

    public function testValueTheHeaderCantCarryIsRefusedWithoutShowingIt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The OAuth header can\'t carry this value of oauth_signature.');

        new OAuthHeaderFactory()->createHeader(new ConsumerSettings('ConsumerKey', 'Consumer"Secret'), null, '');
    }

    public function testCallbackTheHeaderCantCarryIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('The OAuth header can\'t carry this value of oauth_callback.');

        new OAuthHeaderFactory()->createHeader(
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
            null,
            '',
            ['oauth_callback' => 'https://example.com/"callback'],
        );
    }

    /**
     * @return array{0:string,1:int}
     */
    private function readNonceAndTimestamp(): array
    {
        $header = new OAuthHeaderFactory()->createHeader(
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
            null,
            '',
        );
        preg_match('/oauth_nonce="(\d+)".*oauth_timestamp="(\d+)"/', $header->value, $matches);

        return [$matches[1] ?? '', (int) ($matches[2] ?? 0)];
    }
}
