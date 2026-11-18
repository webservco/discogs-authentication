<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * A request that got no response (eg. a network error).
 */
final class TestClientException extends RuntimeException implements ClientExceptionInterface
{
}
