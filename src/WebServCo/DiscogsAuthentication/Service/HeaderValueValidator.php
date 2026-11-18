<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Service;

use SensitiveParameter;

use function preg_match;

/**
 * The OAuth header carries its values as the Discogs documentation shows them: in double quotes, not percent-encoded.
 * A value is valid when it can be sent that way unchanged: not empty, and only visible ASCII characters other than
 * the double quote and the backslash. Discogs' own values (tokens, secrets, verifiers) are letters and digits.
 *
 * phpcs: "Signature of method should be placed on a single line" conflicts with "Expected 0 blank lines between
 * attribute and its target, both are on same line": a #[SensitiveParameter] parameter needs a multi-line signature.
 *
 * @phpcs:disable SlevomatCodingStandard.Classes.RequireSingleLineMethodSignature.RequiredSingleLineSignature
 */
final readonly class HeaderValueValidator
{
    private const string PATTERN = '/^[\x21\x23-\x5B\x5D-\x7E]+$/';

    public function isValid(
        #[SensitiveParameter]
        string $value,
    ): bool {
        return preg_match(self::PATTERN, $value) === 1;
    }
}
