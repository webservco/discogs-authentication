<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Service\HeaderValueValidator;

#[CoversClass(HeaderValueValidator::class)]
final class HeaderValueValidatorTest extends TestCase
{
    #[DataProvider('provideInvalidValues')]
    public function testValueThatNeedsQuotingOrEncodingIsInvalid(string $value): void
    {
        self::assertFalse(new HeaderValueValidator()->isValid($value));
    }

    #[DataProvider('provideValidValues')]
    public function testVisibleAsciiIsValid(string $value): void
    {
        self::assertTrue(new HeaderValueValidator()->isValid($value));
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function provideInvalidValues(): array
    {
        return [
            'backslash' => ['a\\b'],
            'double quote' => ['a"b'],
            'empty' => [''],
            'line break' => ["a\r\nb"],
            'non-ASCII letter' => ['ä'],
            'space' => ['a b'],
            'tab' => ["a\tb"],
        ];
    }

    /**
     * @return array<string,array{0:string}>
     */
    public static function provideValidValues(): array
    {
        return [
            'callback URL with a query' => ['https://example.com/account/callback?a=1&b=2'],
            'percent-encoded' => ['a%20b'],
            'signature' => ['ConsumerSecret&TokenSecret'],
            'signature without token secret' => ['ConsumerSecret&'],
            'token' => ['RequestTokenxxxxxxxxxxxxxxxxxxxxxxxxxxxx'],
        ];
    }
}
