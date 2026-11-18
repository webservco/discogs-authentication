<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service;

use Fig\Http\Message\StatusCodeInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use WebServCo\DiscogsAuthentication\Exception\AuthenticationException;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\AccessToken;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\Identity;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\RequestToken;
use WebServCo\DiscogsAuthentication\Model\Enum\Endpoint;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function parse_str;
use function preg_replace;
use function rtrim;
use function sprintf;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;

/**
 * Reads the responses of the handshake. The token endpoints answer in the body, form-encoded, and their errors are
 * plain text under the same content type (`Invalid consumer.`), not the headers and the 400 of the documentation.
 */
final readonly class ResponseParser
{
    // The part of an error body that goes into the exception message.
    private const int EXCERPT_LENGTH = 200;

    public function __construct(private HeaderValueValidator $headerValueValidator = new HeaderValueValidator())
    {
    }

    public function parseRequestToken(ResponseInterface $response): RequestToken
    {
        $values = $this->readForm($response, Endpoint::RequestToken);
        // OAuth 1.0a: the callback sent with the request token is the one Discogs uses.
        if (($values['oauth_callback_confirmed'] ?? null) !== 'true') {
            throw new AuthenticationException(
                sprintf('%s: the callback is not confirmed.', Endpoint::RequestToken->value),
            );
        }

        return new RequestToken(
            $this->readCredential($values, 'oauth_token', Endpoint::RequestToken),
            $this->readCredential($values, 'oauth_token_secret', Endpoint::RequestToken),
        );
    }

    public function parseAccessToken(ResponseInterface $response): AccessToken
    {
        $values = $this->readForm($response, Endpoint::AccessToken);

        return new AccessToken(
            $this->readCredential($values, 'oauth_token', Endpoint::AccessToken),
            $this->readCredential($values, 'oauth_token_secret', Endpoint::AccessToken),
        );
    }

    public function parseIdentity(ResponseInterface $response): Identity
    {
        $data = $this->readJson($response, Endpoint::Identity);
        $id = $data['id'] ?? null;
        if (!is_int($id) || $id < 1) {
            throw new AuthenticationException(sprintf('%s: the response has no valid id.', Endpoint::Identity->value));
        }

        return new Identity(
            $id,
            $this->readString($data, 'username', Endpoint::Identity),
            $this->readString($data, 'resource_url', Endpoint::Identity),
            $this->readString($data, 'consumer_name', Endpoint::Identity),
        );
    }

    /**
     * Any other status than 200 is an error, whatever the body.
     */
    private function readBody(ResponseInterface $response, Endpoint $endpoint): string
    {
        $body = (string) $response->getBody();
        $statusCode = $response->getStatusCode();
        if ($statusCode !== StatusCodeInterface::STATUS_OK) {
            throw new AuthenticationException(
                rtrim(sprintf('%s: HTTP %d. %s', $endpoint->value, $statusCode, $this->createExcerpt($body))),
                $statusCode,
            );
        }

        return $body;
    }

    /**
     * The start of an error body, on one line, printable ASCII only: Discogs' message is plain text (or JSON), and a
     * proxy error page is HTML.
     */
    private function createExcerpt(string $body): string
    {
        return trim((string) preg_replace('/[^\x20-\x7E]+/', ' ', substr($body, 0, self::EXCERPT_LENGTH)));
    }

    /**
     * The values of a form-encoded body are mixed (`parse_str()`); each one is read with a strict type.
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $values
     * @phpcs:enable
     */
    private function readCredential(array $values, string $name, Endpoint $endpoint): string
    {
        $value = $values[$name] ?? null;
        if (!is_string($value) || !$this->headerValueValidator->isValid($value)) {
            throw new AuthenticationException(sprintf('%s: the response has no valid %s.', $endpoint->value, $name));
        }

        return $value;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     * @phpcs:enable
     */
    private function readForm(ResponseInterface $response, Endpoint $endpoint): array
    {
        parse_str($this->readBody($response, $endpoint), $values);

        return $values;
    }

    /**
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     * @phpcs:enable
     */
    private function readJson(ResponseInterface $response, Endpoint $endpoint): array
    {
        $body = $this->readBody($response, $endpoint);
        try {
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AuthenticationException(
                sprintf('%s: the response is not JSON.', $endpoint->value),
                0,
                $exception,
            );
        }
        if (!is_array($data)) {
            throw new AuthenticationException(sprintf('%s: the response is not a JSON object.', $endpoint->value));
        }

        return $data;
    }

    /**
     * The values of a JSON object are mixed; each one is read with a strict type.
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $data
     * @phpcs:enable
     */
    private function readString(array $data, string $name, Endpoint $endpoint): string
    {
        $value = $data[$name] ?? null;
        if (!is_string($value) || $value === '') {
            throw new AuthenticationException(sprintf('%s: the response has no valid %s.', $endpoint->value, $name));
        }

        return $value;
    }
}
