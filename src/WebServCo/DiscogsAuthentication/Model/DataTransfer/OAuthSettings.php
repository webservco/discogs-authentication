<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\DataTransfer;

use SensitiveParameter;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * The consumer key and secret of a Discogs application, and a user's access token and secret for it.
 *
 * phpcs: "Signature of method should be placed on a single line" conflicts with "Expected 0 blank lines between
 * attribute and its target, both are on same line": a #[SensitiveParameter] parameter needs a multi-line signature.
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature.RequiredSingleLineSignature
 */
final readonly class OAuthSettings implements DataTransferInterface
{
    public function __construct(
        public string $consumerKey,
        #[SensitiveParameter]
        public string $consumerSecret,
        #[SensitiveParameter]
        public string $oauthToken,
        #[SensitiveParameter]
        public string $oauthTokenSecret,
    ) {
    }
}
