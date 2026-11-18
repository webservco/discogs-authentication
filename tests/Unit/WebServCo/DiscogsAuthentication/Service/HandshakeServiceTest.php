<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\RecordingHttpClient;
use Tests\Unit\Assets\ResponseSampleFactory;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Factory\OAuthHeaderFactory;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;
use WebServCo\DiscogsAuthentication\Service\HandshakeService;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;
use WebServCo\DiscogsAuthentication\Service\RequestSender;
use WebServCo\DiscogsAuthentication\Service\ResponseParser;
use WebServCo\Http\Factory\Message\Request\RequestFactory;

/**
 * Each request as the samples were captured with, and each answer from a real response (`Assets/Responses/`).
 */
#[CoversClass(Endpoint::class)]
#[CoversClass(HandshakeService::class)]
#[CoversClass(RequestSender::class)]
#[CoversClass(ResponseParser::class)]
#[UsesClass(AccessToken::class)]
#[UsesClass(AuthenticationException::class)]
#[UsesClass(AuthorizationType::class)]
#[UsesClass(ConsumerSettings::class)]
#[UsesClass(HeaderValueValidator::class)]
#[UsesClass(Identity::class)]
#[UsesClass(OAuthHeaderFactory::class)]
#[UsesClass(RequestSettings::class)]
#[UsesClass(RequestToken::class)]
final class HandshakeServiceTest extends TestCase
{
    private const string CALLBACK_URL = 'https://example.com/account/callback';

    private const string REQUEST_TOKEN = 'RequestTokenxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    private const string REQUEST_TOKEN_SECRET = 'RequestTokenSecretxxxxxxxxxxxxxxxxxxxxxx';

    private const string ACCESS_TOKEN = 'AccessTokenxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

    private const string ACCESS_TOKEN_SECRET = 'AccessTokenSecretxxxxxxxxxxxxxxxxxxxxxxx';

    public function testRequestTokenRequestIsTheDocumentedOne(): void
    {
        $httpClient = $this->createHttpClient('OAuth/request-token');
        $this->createService($httpClient)->fetchRequestToken(self::CALLBACK_URL);
        $request = $httpClient->getLastRequest();
        $authorization = $request->getHeaderLine('Authorization');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.discogs.com/oauth/request_token', (string) $request->getUri());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        self::assertSame('ExampleApplication/1.0', $request->getHeaderLine('User-Agent'));
        self::assertStringStartsWith('OAuth oauth_consumer_key="ConsumerKey",oauth_nonce="', $authorization);
        self::assertStringContainsString(',oauth_signature="ConsumerSecret&",', $authorization);
        self::assertStringEndsWith(',oauth_callback="https://example.com/account/callback"', $authorization);
        self::assertStringNotContainsString('oauth_token=', $authorization);
    }

    public function testRequestTokenIsReadFromTheBody(): void
    {
        $requestToken = $this->createService($this->createHttpClient('OAuth/request-token'))
            ->fetchRequestToken(self::CALLBACK_URL);

        self::assertSame(self::REQUEST_TOKEN, $requestToken->token);
        self::assertSame(self::REQUEST_TOKEN_SECRET, $requestToken->secret);
    }

    #[DataProvider('provideRequestTokenErrors')]
    public function testRequestTokenErrorHasTheStatusAndTheDiscogsMessage(string $sampleName, string $message): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessageIs('oauth/request_token: HTTP 401. ' . $message);

        $this->createService($this->createHttpClient($sampleName))->fetchRequestToken(self::CALLBACK_URL);
    }

    public function testCallbackTheHeaderCantCarryIsRefusedBeforeAnyRequest(): void
    {
        $httpClient = $this->createHttpClient('OAuth/request-token');

        try {
            $this->createService($httpClient)->fetchRequestToken('https://example.com/a b');
            self::fail('The callback URL was accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame(0, $httpClient->getRequestCount());
        }
    }

    public function testAuthorizeUrlHasTheRequestToken(): void
    {
        $service = $this->createService($this->createHttpClient('OAuth/request-token'));

        self::assertSame(
            'https://www.discogs.com/oauth/authorize?oauth_token=' . self::REQUEST_TOKEN,
            $service->createAuthorizeUrl(new RequestToken(self::REQUEST_TOKEN, self::REQUEST_TOKEN_SECRET)),
        );
    }

    public function testAuthorizeUrlCanBeAnother(): void
    {
        $service = new HandshakeService(
            new RequestSender(
                $this->createHttpClient('OAuth/request-token'),
                new RequestFactory(),
                new RequestSettings('ExampleApplication/1.0'),
            ),
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
            'https://example.com/authorize',
        );

        self::assertSame(
            'https://example.com/authorize?oauth_token=' . self::REQUEST_TOKEN,
            $service->createAuthorizeUrl(new RequestToken(self::REQUEST_TOKEN, self::REQUEST_TOKEN_SECRET)),
        );
    }

    public function testAccessTokenRequestIsSignedWithBothSecrets(): void
    {
        $httpClient = $this->createHttpClient('OAuth/access-token');
        $this->fetchAccessToken($httpClient);
        $request = $httpClient->getLastRequest();
        $authorization = $request->getHeaderLine('Authorization');

        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.discogs.com/oauth/access_token', (string) $request->getUri());
        self::assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        self::assertSame('', (string) $request->getBody());
        self::assertStringContainsString(',oauth_token="' . self::REQUEST_TOKEN . '",', $authorization);
        self::assertStringContainsString(
            ',oauth_signature="ConsumerSecret&' . self::REQUEST_TOKEN_SECRET . '",',
            $authorization,
        );
        self::assertStringEndsWith(',oauth_verifier="Verifier01"', $authorization);
    }

    public function testAccessTokenIsReadFromTheBody(): void
    {
        $accessToken = $this->fetchAccessToken($this->createHttpClient('OAuth/access-token'));

        self::assertSame(self::ACCESS_TOKEN, $accessToken->token);
        self::assertSame(self::ACCESS_TOKEN_SECRET, $accessToken->secret);
    }

    public function testVerifierUsedTwiceIsRefused(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessageIs('oauth/access_token: HTTP 401. Invalid request token: ' . self::REQUEST_TOKEN);

        $this->fetchAccessToken($this->createHttpClient('OAuth/access-token-replayed'));
    }

    public function testIdentityRequestIsSignedWithTheAccessToken(): void
    {
        $httpClient = $this->createHttpClient('UserIdentity/identity');
        $this->fetchIdentity($httpClient);
        $request = $httpClient->getLastRequest();
        $authorization = $request->getHeaderLine('Authorization');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('https://api.discogs.com/oauth/identity', (string) $request->getUri());
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertSame('ExampleApplication/1.0', $request->getHeaderLine('User-Agent'));
        self::assertStringContainsString(',oauth_token="' . self::ACCESS_TOKEN . '",', $authorization);
        self::assertStringContainsString(
            ',oauth_signature="ConsumerSecret&' . self::ACCESS_TOKEN_SECRET . '",',
            $authorization,
        );
        self::assertStringEndsWith('"', $authorization);
    }

    public function testIdentityIsReadFromTheBody(): void
    {
        $identity = $this->fetchIdentity($this->createHttpClient('UserIdentity/identity'));

        self::assertSame(1_000_000, $identity->id);
        self::assertSame('xxxxx', $identity->username);
        self::assertSame('https://api.discogs.com/users/xxxxx', $identity->resourceUrl);
        self::assertSame('The Ogger Club (v3)', $identity->consumerName);
    }

    #[DataProvider('provideIdentityErrors')]
    public function testIdentityErrorHasTheStatusAndTheDiscogsMessage(string $sampleName, string $message): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessageIs($message);

        $this->fetchIdentity($this->createHttpClient($sampleName));
    }

    public function testRateLimitHasTheStatus(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(429);
        $this->expectExceptionMessageIs('oauth/identity: HTTP 429.');

        $this->fetchIdentity($this->createHttpClient('rate-limit-exceeded'));
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function provideIdentityErrors(): array
    {
        $message = 'oauth/identity: HTTP 401. {"message":"You must authenticate to access this resource."}';

        return [
            'no credentials' => ['UserIdentity/identity-no-authorization', $message],
            'wrong token secret' => ['UserIdentity/identity-wrong-secret', $message],
        ];
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function provideRequestTokenErrors(): array
    {
        return [
            'unknown consumer key' => ['OAuth/request-token-wrong-key', 'Invalid consumer.'],
            'wrong consumer secret' => ['OAuth/request-token-wrong-secret', 'Invalid signature.'],
        ];
    }

    private function createHttpClient(string $sampleName): RecordingHttpClient
    {
        return new RecordingHttpClient(new ResponseSampleFactory()->createResponse($sampleName));
    }

    private function createService(RecordingHttpClient $httpClient): HandshakeService
    {
        return new HandshakeService(
            new RequestSender($httpClient, new RequestFactory(), new RequestSettings('ExampleApplication/1.0')),
            new ConsumerSettings('ConsumerKey', 'ConsumerSecret'),
        );
    }

    private function fetchAccessToken(RecordingHttpClient $httpClient): AccessToken
    {
        return $this->createService($httpClient)->fetchAccessToken(
            new RequestToken(self::REQUEST_TOKEN, self::REQUEST_TOKEN_SECRET),
            'Verifier01',
        );
    }

    private function fetchIdentity(RecordingHttpClient $httpClient): Identity
    {
        return $this->createService($httpClient)->fetchIdentity(
            new AccessToken(self::ACCESS_TOKEN, self::ACCESS_TOKEN_SECRET),
        );
    }
}
