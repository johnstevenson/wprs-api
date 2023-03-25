<?php declare(strict_types=1);

namespace Wprs\Api\Http;

interface DownloaderInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): Response;

    /**
     * @param array<string> $urls
     * @param array<string, mixed> $options
     * @return array<Response>
     */
    public function getBatch(array $urls, array $options = []): array;
}
