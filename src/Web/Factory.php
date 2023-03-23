<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;

class Factory
{
    public static function createEndpoint(
        int $endpointType,
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $name = self::getEndpointName($endpointType);
        $class = self::getEndPointClass($name);

        if (!class_exists($class)) {
            self::notifyNotImplemented($name);
        }

        return new $class($discipline, $filter, $downloader);
    }

    public static function createParams(int $endpointType, ...$args): ParamsInterface
    {
        $name = self::getEndpointName($endpointType);
        $class = self::getEndPointClass($name, 'Params');

        if (!class_exists($class)) {
            self::notifyNotImplemented($name);
        }

        return new $class(...$args);
    }

    private static function getEndpointName(int $endpoint): string
    {
        return ucfirst(Rank::getEndpoint($endpoint));
    }

    private static function getEndPointClass(string $name, string $suffix = ''): string
    {
        $format = '\\%s\\Endpoint\\%s\\%s'.$suffix;

        return sprintf($format, __NAMESPACE__, $name, $name);
    }

    private static function notifyNotImplemented($name)
    {
        $msg = sprintf('The %s endpoint has not been implemented.', $name);
        throw new \RuntimeException($msg);
    }
}
