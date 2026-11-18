<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\RecordingHttpClient;
use Tests\Unit\Assets\ResponseSampleFactory;
use Tests\Unit\Assets\TestClientException;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;
use WebServCo\DiscogsAuthentication\Service\RequestSender;
use WebServCo\Http\Factory\Message\Request\RequestFactory;

#[CoversClass(Endpoint::class)]
#[CoversClass(RequestSender::class)]
#[UsesClass(AuthenticationException::class)]
#[UsesClass(RequestSettings::class)]
final class RequestSenderTest extends TestCase
{
    public function testResponseIsReturnedWhateverItsStatus(): void
    {
        $response = new ResponseSampleFactory()->createResponse('rate-limit-exceeded');

        self::assertSame(
            $response,
            $this->createSender(new RecordingHttpClient($response))->send(Endpoint::Identity, $this->createHeader()),
        );
    }

    /**
     * As the Discogs documentation shows each request.
     */
    #[DataProvider('provideEndpoints')]
    public function testRequestIsTheDocumentedOne(Endpoint $endpoint, string $method, string $contentType): void
    {
        $httpClient = new RecordingHttpClient(new ResponseSampleFactory()->createResponse('UserIdentity/identity'));
        $this->createSender($httpClient)->send($endpoint, $this->createHeader());
        $request = $httpClient->getLastRequest();

        self::assertSame($method, $request->getMethod());
        self::assertSame('https://api.discogs.com/' . $endpoint->value, (string) $request->getUri());
        self::assertSame($contentType, $request->getHeaderLine('Content-Type'));
        self::assertSame('ExampleApplication/1.0', $request->getHeaderLine('User-Agent'));
        self::assertSame('OAuth oauth_consumer_key="ConsumerKey"', $request->getHeaderLine('Authorization'));
        self::assertSame('', (string) $request->getBody());
    }

    public function testApiUrlWithoutTrailingSlash(): void
    {
        $httpClient = new RecordingHttpClient(new ResponseSampleFactory()->createResponse('UserIdentity/identity'));
        new RequestSender($httpClient, new RequestFactory(), new RequestSettings('Agent', 'https://api.example.com'))
            ->send(Endpoint::Identity, $this->createHeader());

        self::assertSame('https://api.example.com/oauth/identity', (string) $httpClient->getLastRequest()->getUri());
    }

    public function testRequestWithoutResponseFails(): void
    {
        $clientException = new TestClientException('Connection refused.');

        try {
            $this->createSender(new RecordingHttpClient(null, $clientException))
                ->send(Endpoint::RequestToken, $this->createHeader());
            self::fail('The request did not fail.');
        } catch (AuthenticationException $exception) {
            self::assertSame('oauth/request_token: the request failed.', $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertSame($clientException, $exception->getPrevious());
        }
    }

    /**
     * @return array<string,array{0:\WebServCo\DiscogsAuthentication\Model\Enum\Endpoint,1:string,2:string}>
     */
    public static function provideEndpoints(): array
    {
        return [
            'access token' => [Endpoint::AccessToken, 'POST', 'application/x-www-form-urlencoded'],
            'identity' => [Endpoint::Identity, 'GET', ''],
            'request token' => [Endpoint::RequestToken, 'GET', 'application/x-www-form-urlencoded'],
        ];
    }

    private function createHeader(): StringString
    {
        return new StringString('Authorization', 'OAuth oauth_consumer_key="ConsumerKey"');
    }

    private function createSender(RecordingHttpClient $httpClient): RequestSender
    {
        return new RequestSender($httpClient, new RequestFactory(), new RequestSettings('ExampleApplication/1.0'));
    }
}
