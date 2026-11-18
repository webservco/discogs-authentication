<?php

declare(strict_types=1);

namespace WebServCo\DiscogsAuthentication\Exception;

use RuntimeException;

/**
 * A step of the login failed: the request, the response, or the callback.
 *
 * The code is the HTTP status when Discogs answered with another one than 200 (eg. 401, 429), and 0 otherwise. The
 * message names the endpoint and has no credentials.
 */
final class AuthenticationException extends RuntimeException
{
}
