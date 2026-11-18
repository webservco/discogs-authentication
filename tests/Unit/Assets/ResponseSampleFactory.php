<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use WebServCo\Http\Factory\Message\Response\ResponseFactory;
use WebServCo\Http\Factory\Message\Stream\StreamFactory;
use WebServCo\Http\Service\Message\Response\StatusCodeService;

use function array_shift;
use function explode;
use function file;
use function file_get_contents;
use function is_file;
use function sprintf;
use function trim;

use const FILE_IGNORE_NEW_LINES;

/**
 * Builds PSR-7 responses from the real Discogs responses in `Responses/` (see its README).
 */
final readonly class ResponseSampleFactory
{
    private const string DIRECTORY = __DIR__ . '/Responses';

    public function createResponse(string $name): ResponseInterface
    {
        $streamFactory = new StreamFactory();
        $headerLines = $this->readHeaderLines($name);
        $statusLine = array_shift($headerLines);
        if ($statusLine === null) {
            throw new UnexpectedValueException(sprintf('Sample "%s" has no status line.', $name));
        }

        $response = new ResponseFactory(new StatusCodeService(), $streamFactory)
            ->createResponse((int) (explode(' ', $statusLine)[1] ?? 0))
            ->withBody($streamFactory->createStream($this->readBody($name)));
        foreach ($headerLines as $headerLine) {
            $parts = explode(':', $headerLine, 2);
            if (!isset($parts[1])) {
                throw new UnexpectedValueException(sprintf('Invalid header line: "%s".', $headerLine));
            }

            $response = $response->withAddedHeader($parts[0], trim($parts[1]));
        }

        return $response;
    }

    /**
     * The token responses are form-encoded (`.txt`), the others JSON.
     */
    private function readBody(string $name): string
    {
        $path = sprintf('%s/%s.txt', self::DIRECTORY, $name);
        if (!is_file($path)) {
            $path = sprintf('%s/%s.json', self::DIRECTORY, $name);
        }
        $body = file_get_contents($path);
        if ($body === false) {
            throw new UnexpectedValueException(sprintf('Could not read the body of sample "%s".', $name));
        }

        return $body;
    }

    /**
     * @return array<int,string>
     */
    private function readHeaderLines(string $name): array
    {
        $lines = file(sprintf('%s/%s.headers.txt', self::DIRECTORY, $name), FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new UnexpectedValueException(sprintf('Could not read the headers of sample "%s".', $name));
        }

        $headerLines = [];
        foreach ($lines as $line) {
            $headerLine = trim($line);
            if ($headerLine === '') {
                continue;
            }

            $headerLines[] = $headerLine;
        }

        return $headerLines;
    }
}
