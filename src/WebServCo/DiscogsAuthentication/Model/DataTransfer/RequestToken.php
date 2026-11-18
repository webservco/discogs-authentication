<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Model\DataTransfer;

use SensitiveParameter;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * The temporary token of a login, from `oauth/request_token`. The token goes to the user in the authorize URL, the
 * secret stays on the server until the callback (eg. in the session). Discogs lets it expire after 15 minutes.
 *
 * phpcs: "Signature of method should be placed on a single line" conflicts with "Expected 0 blank lines between
 * attribute and its target, both are on same line": a #[SensitiveParameter] parameter needs a multi-line signature.
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature.RequiredSingleLineSignature
 */
final readonly class RequestToken implements DataTransferInterface
{
    public function __construct(
        public string $token,
        #[SensitiveParameter]
        public string $secret,
    ) {
    }
}
