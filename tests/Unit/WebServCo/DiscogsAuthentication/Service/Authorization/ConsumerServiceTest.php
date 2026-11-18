<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\DiscogsAuthentication\Service\Authorization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WebServCo\DiscogsAuthentication\Model\DataTransfer\ConsumerSettings;
use WebServCo\DiscogsAuthentication\Model\Enum\AuthorizationType;
use WebServCo\DiscogsAuthentication\Service\Authorization\ConsumerService;

#[CoversClass(AuthorizationType::class)]
#[CoversClass(ConsumerService::class)]
#[UsesClass(ConsumerSettings::class)]
final class ConsumerServiceTest extends TestCase
{
    public function testCredentialsGoInTheAuthorizationHeader(): void
    {
        $header = new ConsumerService(new ConsumerSettings('the-key', 'the-secret'))->createAuthorizationHeader();

        self::assertSame('Authorization', $header->key);
        self::assertSame('Discogs key=the-key, secret=the-secret', $header->value);
    }
}
