<?php declare(strict_types=1);

namespace Wprs\Api\Http;

interface DownloaderInterface
{
    public function get(string $url): Response;

    /**
     * @param array<string> $urls
     * @return array<Response>
     */
    public function getBatch(array $urls): array;

    /**
     * @param array<int, mixed> $curlOptions
     */
    public function setCurlOptions(array $curlOptions): void;
}
