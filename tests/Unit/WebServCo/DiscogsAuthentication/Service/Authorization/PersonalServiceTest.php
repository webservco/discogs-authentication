<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service\Authorization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\PersonalSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Service\Authorization\PersonalService;

#[CoversClass(AuthorizationType::class)]
#[CoversClass(PersonalService::class)]
#[UsesClass(PersonalSettings::class)]
final class PersonalServiceTest extends TestCase
{
    public function testTokenGoesInTheAuthorizationHeader(): void
    {
        $header = new PersonalService(new PersonalSettings('the-token'))->createAuthorizationHeader();

        self::assertSame('Authorization', $header->key);
        self::assertSame('Discogs token=the-token', $header->value);
    }
}
