<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

use function count;

/**
 * Answers every request with one response, or fails it with one exception, and keeps the requests it was sent.
 */
final class RecordingHttpClient implements ClientInterface
{
    /**
     * @var array<int,\Psr\Http\Message\RequestInterface>
     */
    private array $requests = [];

    public function __construct(
        private readonly ?ResponseInterface $response,
        private readonly ?ClientExceptionInterface $exception = null,
    ) {
    }

    public function getLastRequest(): RequestInterface
    {
        $request = $this->requests[count($this->requests) - 1] ?? null;
        if ($request === null) {
            throw new UnexpectedValueException('No request was sent.');
        }

        return $request;
    }

    public function getRequestCount(): int
    {
        return count($this->requests);
    }

    #[Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        if ($this->exception !== null) {
            throw $this->exception;
        }
        if ($this->response === null) {
            throw new UnexpectedValueException('The client has no response.');
        }

        return $this->response;
    }
}
