<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Contract;

use WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;

/**
 * The Discogs OAuth 1.0a handshake. The fetch methods send one request each, and throw `AuthenticationException` when
 * it fails.
 */
interface HandshakeServiceInterface
{
    /**
     * Step 1: the request token. Discogs sends the user back to the callback URL after the authorize page.
     *
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    public function fetchRequestToken(string $callbackUrl): RequestToken;

    /**
     * Step 2: where to send the user to authorize the application.
     */
    public function createAuthorizeUrl(RequestToken $requestToken): string;

    /**
     * Step 3: the access token, with the verifier of the callback (`CallbackReaderInterface`).
     *
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    public function fetchAccessToken(RequestToken $requestToken, string $verifier): AccessToken;

    /**
     * Step 4: the user the access token belongs to.
     *
     * @throws \WebServCo\DiscogsAuthentication\Exception\AuthenticationException
     */
    public function fetchIdentity(AccessToken $accessToken): Identity;
}
