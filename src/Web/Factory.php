<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Endpoint\FilterInterface;

class Factory
{
    /**
     * @template T of object
     * @param class-string<T> $endpoint
     * @return T
     */
    public static function createEndpoint(
        string $endpoint,
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ): object {
        $name = System::getEndpoint($endpoint);

        if (!class_exists($endpoint)) {
            $msg = sprintf('The %s endpoint has not been implemented.', $name);
            throw new \RuntimeException($msg);
        }

        return new $endpoint($discipline, $filter, $downloader);
    }
}
