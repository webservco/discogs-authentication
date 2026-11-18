<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\DataTransfer;

use SensitiveParameter;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * A user's access token and secret, from `oauth/access_token`. Valid until the user revokes the application on
 * Discogs.
 *
 * phpcs: "Signature of method should be placed on a single line" conflicts with "Expected 0 blank lines between
 * attribute and its target, both are on same line": a #[SensitiveParameter] parameter needs a multi-line signature.
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature.RequiredSingleLineSignature
 */
final readonly class AccessToken implements DataTransferInterface
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
        #[SensitiveParameter]
        public string $secret,
    ) {
    }
}
