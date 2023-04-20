<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers;

use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\Response;

class MockDownloader extends HttpDownloader
{
    public const MODE_SIMPLE = 0;
    public const MODE_FULL = 1;

    private int $mode;
    private int $urlCount = 0;
    private string $contents;
    private ?int $statusCode = null;

    /**
     * @param string|int $value
     */
    public function __construct($value, int $mode = self::MODE_SIMPLE)
    {
        if (is_string($value)) {
            $this->contents = $value;
            $this->statusCode = null;
        } else {
            $this->statusCode = $value;
            $this->contents = '';
        }

        $this->mode = $mode;

        if ($this->mode === self::MODE_FULL) {
            $worker = new MockWorker($this->contents, $this->statusCode);
        } else {
            $worker = null;
        }

        parent::__construct($worker);
    }

    public function get(string $url): Response
    {
        $result = $this->getBatch([$url]);

        return $result[0];
    }

    public function getBatch(array $urls): array
    {
        if (count($urls) === 0) {
            throw new \InvalidArgumentException('Test not run, urls array cannot be empty');
        }

        $this->urlCount += count($urls);

        if ($this->mode === self::MODE_SIMPLE) {
            return $this->runSimple($urls);
        }

        return parent::getBatch($urls);
    }

    /**
     * @return array<int, mixed>
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    public function getUrlCount(): int
    {
        return $this->urlCount;
    }

    /**
     * @param non-empty-array<string> $urls
     * @return array<Response>
     */
    private function runSimple(array $urls): array
    {
        if ($this->statusCode !== null) {
            $msg = sprintf('http status %d, url: %s', $this->statusCode, $urls[0]);
            throw new \RuntimeException($msg);
        }

        // we just return a single response to cut parsing time
        $response = new Response(0, $urls[0], $this->contents);

        return [$response];
    }
}
