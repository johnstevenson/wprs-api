<?php declare(strict_types=1);

namespace Wprs\Api\Http;

interface DownloaderInterface
{
    public function get(string $url, array $options = []): Response;

    public function getBatch(array $urls, array $options = []): array;
}
