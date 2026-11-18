# webservco/discogs-authentication

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

Logs a user in with Discogs (OAuth 1.0a: request token, authorize URL, access token, identity) and builds the Authorization header of every Discogs API request.

- Requires: a [Discogs application](https://www.discogs.com/settings/developers) (consumer key and secret), a [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and a [PSR-17](https://www.php-fig.org/psr/psr-17/) request factory
- Related: `webservco/http-client` (a PSR-18 client), `webservco/http` (a PSR-17 factory), `webservco/data` (`StringString`, the header), `webservco/session` (keeps the request token until the callback)

---

## Installation

```shell
composer require webservco/discogs-authentication
```

---

## Usage

### Login

```php
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestSettings;
use WebServCo\DiscogsAuthentication\Service\HandshakeService;
use WebServCo\DiscogsAuthentication\Service\RequestSender;

// \WebServCo\DiscogsAuthentication\Contract\HandshakeServiceInterface
$handshakeService = new HandshakeService(
    // $httpClient: \Psr\Http\Client\ClientInterface, $requestFactory: \Psr\Http\Message\RequestFactoryInterface
    new RequestSender($httpClient, $requestFactory, new RequestSettings('ExampleApplication/1.0 +https://example.com')),
    new ConsumerSettings($consumerKey, $consumerSecret),
);

// The login page: a GET.
$requestToken = $handshakeService->fetchRequestToken('https://example.com/account/callback');
// Keep the request token on the server until the callback, eg. in the session. Then redirect the user.
$authorizeUrl = $handshakeService->createAuthorizeUrl($requestToken);
```

Discogs sends the user back to the callback URL:

```php
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Service\CallbackReader;

// \WebServCo\DiscogsAuthentication\Contract\CallbackReaderInterface
$callbackReader = new CallbackReader();
$queryParams = $request->getQueryParams();
if ($callbackReader->isDenied($queryParams)) {
    // The user refused: no login.
}

// The stored request token. Remove it from the session: it works once.
$requestToken = new RequestToken($storedToken, $storedSecret);
$verifier = $callbackReader->readVerifier($queryParams, $requestToken);
// \WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken: keep it for requests on behalf of the user.
$accessToken = $handshakeService->fetchAccessToken($requestToken, $verifier);
// \WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity: the ID is the user's key, the username can change.
$identity = $handshakeService->fetchIdentity($accessToken);
```

### Requests to the API

```php
use WebServCo\DiscogsAuthentication\Model\DataTransfer\OAuthSettings;
use WebServCo\DiscogsAuthentication\Service\Authorization\OAuthService;

// On behalf of a user. Or `ConsumerService` (the application), `PersonalService` (a personal access token).
// \WebServCo\DiscogsAuthentication\Contract\AuthorizationServiceInterface
$authorizationService = new OAuthService(
    new OAuthSettings($consumerKey, $consumerSecret, $accessToken->token, $accessToken->secret),
);
// \WebServCo\Data\DataTransferObject\KeyValue\StringString
$header = $authorizationService->createAuthorizationHeader();
$request = $request->withHeader($header->key, $header->value);
```

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\HandshakeServiceInterface` | `Service\HandshakeService` | Request token, authorize URL, access token, identity. |
| `Contract\CallbackReaderInterface` | `Service\CallbackReader` | Reads the callback: a refusal, or the verifier of this login. |
| `Contract\AuthorizationServiceInterface` | `Service\Authorization\ConsumerService`, `PersonalService`, `OAuthService` | The `Authorization` header of a request: the application, a personal access token, a user. |

Namespaces are relative to `WebServCo\DiscogsAuthentication\`. Without a contract: `Service\RequestSender` (sends a request of the handshake), `Factory\OAuthHeaderFactory`, `Service\ResponseParser`, `Service\HeaderValueValidator`.

---

## Notes

From real responses, captured in 2026-11 (`tests/Unit/Assets/Responses/`).

- **Errors.** The fetch methods throw `Exception\AuthenticationException`: no response, another status than 200, or a response without a valid value. Its code is the HTTP status (`401`, `429`), or `0`. Its message names the endpoint and has Discogs' text, never a credential.
- **The header.** Values go in double quotes, not percent-encoded, as the Discogs documentation shows them. A value that can't be sent that way unchanged (empty, a space, a double quote, a backslash, a character outside ASCII) is refused: `InvalidArgumentException` for a callback URL, `AuthenticationException` for a value from Discogs or the callback.
- **Callback URL.** Give the application on Discogs no callback URL: each environment sends its own with the request token, and Discogs confirms it (`oauth_callback_confirmed=true`, checked).
- **Token responses.** The values come in the body, form-encoded, not in headers. An error is a 401 with a plain text body (`Invalid consumer.`, `Invalid signature.`), not the documented 400.
- **Access token.** Signed with the consumer secret and the request token secret, as OAuth 1.0a says. The documentation signs it with the consumer secret only.
- **A request token** expires 15 minutes after it is issued, and works once: a second access token request gets `401 Invalid request token`.
- **Refusal.** Discogs sends the user to the callback with only `denied=<request token>`.
- **Rate limit.** A login sends three requests. Discogs counted every request from one IP once, whatever the application or token: another application on the same server shares the count.
- **Content Security Policy.** A `form-action` directive also applies to the redirects after a form, so a login started by a POST form can't redirect to the authorize URL. Start it with a GET link, or allow `https://www.discogs.com`.

---

## Development

```shell
# Start project.
ddev start
# Install dependencies.
ddev c u
# Run all validations.
ddev c c
# Run unit tests, with coverage (needs Xdebug, which DDEV leaves off).
ddev xdebug on && ddev c test:dox; ddev xdebug off
```

---

## License

[MIT](LICENSE.md)
