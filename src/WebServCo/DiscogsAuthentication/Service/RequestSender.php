<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;

use function rtrim;
use function sprintf;

/**
 * Sends a request of the handshake, as the Discogs documentation shows it, and returns the response whatever its
 * status.
 */
final readonly class RequestSender
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private RequestSettings $requestSettings,
    ) {
    }

    /**
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException No response (eg. a network error).
     */
    public function send(Endpoint $endpoint, StringString $authorizationHeader): ResponseInterface
    {
        $request = $this->requestFactory
            ->createRequest(
                $endpoint->getMethod(),
                sprintf('%s/%s', rtrim($this->requestSettings->apiUrl, '/'), $endpoint->value),
            )
            ->withHeader($authorizationHeader->key, $authorizationHeader->value)
            ->withHeader('User-Agent', $this->requestSettings->userAgent);
        if ($endpoint->isTokenEndpoint()) {
            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        }

        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new AuthenticationException(sprintf('%s: the request failed.', $endpoint->value), 0, $exception);
        }
    }
}
