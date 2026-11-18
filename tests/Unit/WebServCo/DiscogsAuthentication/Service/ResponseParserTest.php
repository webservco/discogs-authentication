<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;
use WebServCo\DiscogsAuthentication\Service\ResponseParser;
use WebServCo\Http\Factory\Message\Response\ResponseFactory;
use WebServCo\Http\Factory\Message\Stream\StreamFactory;
use WebServCo\Http\Service\Message\Response\StatusCodeService;

use function str_repeat;

/**
 * Responses Discogs wasn't seen sending: every response here is hand-written. The real ones are in
 * `HandshakeServiceTest`.
 */
#[CoversClass(ResponseParser::class)]
#[UsesClass(AccessToken::class)]
#[UsesClass(AuthenticationException::class)]
#[UsesClass(Endpoint::class)]
#[UsesClass(HeaderValueValidator::class)]
#[UsesClass(Identity::class)]
#[UsesClass(RequestToken::class)]
final class ResponseParserTest extends TestCase
{
    public function testRequestTokenWithoutConfirmedCallbackIsRefused(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('oauth/request_token: the callback is not confirmed.');

        new ResponseParser()->parseRequestToken($this->createResponse('oauth_token=Token&oauth_token_secret=Secret'));
    }

    #[DataProvider('provideInvalidTokenBodies')]
    public function testAccessTokenWithoutValidValueIsRefused(string $body, string $name): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessageIs('oauth/access_token: the response has no valid ' . $name . '.');

        new ResponseParser()->parseAccessToken($this->createResponse($body));
    }

    #[DataProvider('provideInvalidIdentities')]
    public function testIdentityWithoutValidValueIsRefused(string $body, string $name): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('oauth/identity: the response has no valid ' . $name . '.');

        new ResponseParser()->parseIdentity($this->createResponse($body));
    }

    public function testIdentityThatIsNotJsonIsRefused(): void
    {
        try {
            new ResponseParser()->parseIdentity($this->createResponse('<html></html>'));
            self::fail('The response was accepted.');
        } catch (AuthenticationException $exception) {
            self::assertSame('oauth/identity: the response is not JSON.', $exception->getMessage());
            self::assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testIdentityThatIsNotAnObjectIsRefused(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessageIs('oauth/identity: the response is not a JSON object.');

        new ResponseParser()->parseIdentity($this->createResponse('"text"'));
    }

    /**
     * A proxy error page: the message keeps the start of the body, on one line.
     */
    public function testErrorBodyIsShortenedToOneLine(): void
    {
        $body = "<html>\r\n<head>\t<title>502 Bad Gateway</title></head>\n" . str_repeat('x', 300);

        try {
            new ResponseParser()->parseIdentity($this->createResponse($body, 502));
            self::fail('The response was accepted.');
        } catch (AuthenticationException $exception) {
            self::assertSame(502, $exception->getCode());
            // The first 200 bytes: 53 before the `x`, then 147 `x`.
            self::assertSame(
                'oauth/identity: HTTP 502. <html> <head> <title>502 Bad Gateway</title></head> ' . str_repeat('x', 147),
                $exception->getMessage(),
            );
        }
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function provideInvalidIdentities(): array
    {
        return [
            'consumer name not a string' => [
                '{"id":1,"username":"a","resource_url":"b","consumer_name":1}',
                'consumer_name',
            ],
            'id missing' => ['{"username":"a","resource_url":"b","consumer_name":"c"}', 'id'],
            'id not a number' => ['{"id":"1","username":"a","resource_url":"b","consumer_name":"c"}', 'id'],
            'id zero' => ['{"id":0,"username":"a","resource_url":"b","consumer_name":"c"}', 'id'],
            'username empty' => ['{"id":1,"username":"","resource_url":"b","consumer_name":"c"}', 'username'],
            'username missing' => ['{"id":1,"resource_url":"b","consumer_name":"c"}', 'username'],
        ];
    }

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function provideInvalidTokenBodies(): array
    {
        return [
            'secret missing' => ['oauth_token=Token', 'oauth_token_secret'],
            'token as a list' => ['oauth_token[]=Token&oauth_token_secret=Secret', 'oauth_token'],
            'token empty' => ['oauth_token=&oauth_token_secret=Secret', 'oauth_token'],
            'token with a double quote' => ['oauth_token=To%22ken&oauth_token_secret=Secret', 'oauth_token'],
        ];
    }

    private function createResponse(string $body, int $statusCode = 200): ResponseInterface
    {
        $streamFactory = new StreamFactory();

        return new ResponseFactory(new StatusCodeService(), $streamFactory)
            ->createResponse($statusCode)
            ->withBody($streamFactory->createStream($body));
    }
}
