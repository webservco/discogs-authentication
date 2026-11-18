<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Factory;

use InvalidArgumentException;
use SensitiveParameter;
use WebServCo\Data\DataTransferObject\KeyValue\StringString;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;

use function implode;
use function random_int;
use function sprintf;
use function time;

/**
 * The `Authorization: OAuth …` header of the handshake and of every request on behalf of a user, with the PLAINTEXT
 * signature: the consumer secret, `&`, and the token secret (empty for the request token).
 *
 * phpcs: "Signature of method should be placed on a single line" conflicts with "Expected 0 blank lines between
 * attribute and its target, both are on same line": a #[SensitiveParameter] parameter needs a multi-line signature.
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature.RequiredSingleLineSignature
 */
final readonly class OAuthHeaderFactory
{
    // The nonce is a random number, as the one Discogs has always accepted from `mt_rand()`.
    private const int NONCE_MAXIMUM = 2_147_483_647;

    public function __construct(private HeaderValueValidator $headerValueValidator = new HeaderValueValidator())
    {
    }

    /**
     * @param array<string,string> $extraParameters `oauth_callback` or `oauth_verifier`, sent after the others.
     * @throws \InvalidArgumentException A value the header can't carry unchanged (`HeaderValueValidator`).
     */
    public function createHeader(
        ConsumerSettings $consumerSettings,
        ?string $token,
        #[SensitiveParameter]
        string $tokenSecret,
        array $extraParameters = [],
    ): StringString {
        $parameters = [
            'oauth_consumer_key' => $consumerSettings->consumerKey,
            'oauth_nonce' => (string) random_int(1, self::NONCE_MAXIMUM),
        ];
        if ($token !== null) {
            $parameters['oauth_token'] = $token;
        }
        $parameters['oauth_signature'] = sprintf('%s&%s', $consumerSettings->consumerSecret, $tokenSecret);
        $parameters['oauth_signature_method'] = 'PLAINTEXT';
        $parameters['oauth_timestamp'] = (string) time();

        return new StringString(
            AuthorizationType::HEADER_NAME,
            AuthorizationType::OAuth->createHeaderValue($this->formatParameters([...$parameters, ...$extraParameters])),
        );
    }

    /**
     * @param array<string,string> $parameters
     */
    private function formatParameters(array $parameters): string
    {
        $pairs = [];
        foreach ($parameters as $name => $value) {
            if (!$this->headerValueValidator->isValid($value)) {
                // The value isn't in the message: it can be a secret.
                throw new InvalidArgumentException(sprintf('The OAuth header can\'t carry this value of %s.', $name));
            }

            $pairs[] = sprintf('%s="%s"', $name, $value);
        }

        return implode(',', $pairs);
    }
}
