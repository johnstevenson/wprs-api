<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Http\Response;

class MockDownloader implements DownloaderInterface
{
    private string $html;
    private ?int $statusCode = null;

    /**
     * @param string|int $value
     */
    public function __construct($value)
    {
        if (is_string($value)) {
            $this->html = $value;
        } else {
            $this->statusCode = $value;
            $this->html = '';
        }

    }
    public function get(string $url, array $options = []): Response
    {
        $result = $this->getBatch([$url]);

        return $result[0];
    }

    public function getBatch(array $urls, array $options = []): array
    {
        $url = $urls[0] ?? null;

        if ($url === null) {
            throw new \RuntimeException('urls array cannot be empty');
        }

        if ($this->statusCode !== null) {
            $msg = sprintf('http error %d downloading %s', $this->statusCode, $url);
            throw new \RuntimeException();
        }

        $response = new Response(0, $url, $this->html);

        return [$response];
    }
}
