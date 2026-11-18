<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Service\CallbackReader;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;

/**
 * The query of the callback URL as Discogs sends it: `oauth_token` and `oauth_verifier` after an authorization,
 * only `denied` after a refusal.
 */
#[CoversClass(CallbackReader::class)]
#[UsesClass(AuthenticationException::class)]
#[UsesClass(HeaderValueValidator::class)]
#[UsesClass(RequestToken::class)]
final class CallbackReaderTest extends TestCase
{
    private const string REQUEST_TOKEN = 'RequestTokenxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    public function testRefusalIsDenied(): void
    {
        self::assertTrue(new CallbackReader()->isDenied(['denied' => self::REQUEST_TOKEN]));
    }

    public function testAuthorizationIsNotDenied(): void
    {
        self::assertFalse(
            new CallbackReader()->isDenied(['oauth_token' => self::REQUEST_TOKEN, 'oauth_verifier' => 'Verifier01']),
        );
    }

    public function testVerifierIsRead(): void
    {
        self::assertSame(
            'Verifier01',
            new CallbackReader()->readVerifier(
                ['oauth_token' => self::REQUEST_TOKEN, 'oauth_verifier' => 'Verifier01'],
                $this->createRequestToken(),
            ),
        );
    }

    /**
     * @param array<string,array<int,string>|string> $queryParams
     */
    #[DataProvider('provideInvalidTokens')]
    public function testCallbackOfAnotherLoginIsRefused(array $queryParams): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('Callback: oauth_token is not the request token of this login.');

        new CallbackReader()->readVerifier($queryParams, $this->createRequestToken());
    }

    /**
     * @param array<string,array<int,string>|string> $queryParams
     */
    #[DataProvider('provideInvalidVerifiers')]
    public function testInvalidVerifierIsRefused(array $queryParams): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('Callback: oauth_verifier is missing or not valid.');

        new CallbackReader()->readVerifier($queryParams, $this->createRequestToken());
    }

    /**
     * @return array<string,array{0:array<string,array<int,string>|string>}>
     */
    public static function provideInvalidTokens(): array
    {
        return [
            'another token' => [['oauth_token' => 'AnotherToken', 'oauth_verifier' => 'Verifier01']],
            'missing' => [['oauth_verifier' => 'Verifier01']],
            'refusal' => [['denied' => self::REQUEST_TOKEN]],
            'token as a list' => [['oauth_token' => [self::REQUEST_TOKEN], 'oauth_verifier' => 'Verifier01']],
        ];
    }

    /**
     * @return array<string,array{0:array<string,array<int,string>|string>}>
     */
    public static function provideInvalidVerifiers(): array
    {
        return [
            'double quote' => [['oauth_token' => self::REQUEST_TOKEN, 'oauth_verifier' => 'Verifier"01']],
            'empty' => [['oauth_token' => self::REQUEST_TOKEN, 'oauth_verifier' => '']],
            'missing' => [['oauth_token' => self::REQUEST_TOKEN]],
            'verifier as a list' => [['oauth_token' => self::REQUEST_TOKEN, 'oauth_verifier' => ['Verifier01']]],
        ];
    }

    private function createRequestToken(): RequestToken
    {
        return new RequestToken(self::REQUEST_TOKEN, 'RequestTokenSecretxxxxxxxxxxxxxxxxxxxxxx');
    }
}
