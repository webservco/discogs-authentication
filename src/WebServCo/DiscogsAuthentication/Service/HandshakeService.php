<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service;

use Override;
use WebServCo\DiscogsAuthentication\Contract\HandshakeServiceInterface;
use WebServCo\DiscogsAuthentication\Factory\OAuthHeaderFactory;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;

use function http_build_query;
use function sprintf;

/**
 * Each fetch method sends one request, and each counts in the Discogs rate limit: a login sends three.
 */
final readonly class HandshakeService implements HandshakeServiceInterface
{
    // The page where the user authorizes the application, on the Discogs website.
    public const string AUTHORIZE_URL = 'https://www.discogs.com/oauth/authorize';

    public function __construct(
        private RequestSender $requestSender,
        private ConsumerSettings $consumerSettings,
        private string $authorizeUrl = self::AUTHORIZE_URL,
        private OAuthHeaderFactory $oauthHeaderFactory = new OAuthHeaderFactory(),
        private ResponseParser $responseParser = new ResponseParser(),
    ) {
    }

    /**
     * @throws \InvalidArgumentException A callback URL the header can't carry unchanged (`HeaderValueValidator`).
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    #[Override]
    public function fetchRequestToken(string $callbackUrl): RequestToken
    {
        $header = $this->oauthHeaderFactory->createHeader(
            $this->consumerSettings,
            null,
            '',
            ['oauth_callback' => $callbackUrl],
        );

        return $this->responseParser->parseRequestToken($this->requestSender->send(Endpoint::RequestToken, $header));
    }

    #[Override]
    public function createAuthorizeUrl(RequestToken $requestToken): string
    {
        return sprintf('%s?%s', $this->authorizeUrl, http_build_query(['oauth_token' => $requestToken->token]));
    }

    /**
     * The request is signed with the request token secret too, as OAuth 1.0a says. The Discogs documentation signs it
     * with the consumer secret only.
     *
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    #[Override]
    public function fetchAccessToken(RequestToken $requestToken, string $verifier): AccessToken
    {
        $header = $this->oauthHeaderFactory->createHeader(
            $this->consumerSettings,
            $requestToken->token,
            $requestToken->secret,
            ['oauth_verifier' => $verifier],
        );

        return $this->responseParser->parseAccessToken($this->requestSender->send(Endpoint::AccessToken, $header));
    }

    /**
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    #[Override]
    public function fetchIdentity(AccessToken $accessToken): Identity
    {
        $header = $this->oauthHeaderFactory->createHeader(
            $this->consumerSettings,
            $accessToken->token,
            $accessToken->secret,
        );

        return $this->responseParser->parseIdentity($this->requestSender->send(Endpoint::Identity, $header));
    }
}
