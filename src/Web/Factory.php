<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;

class Factory
{
    /**
     * @return Competition|Competitions|Pilots
     */
    public static function createEndpoint(
        int $endpointType,
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $name = self::getEndpointName($endpointType);
        $class = self::getEndPointClass($name);

        if (!class_exists($class)) {
            $msg = sprintf('The %s endpoint has not been implemented.', $name);
            throw new \RuntimeException($msg);
        }

        return new $class($discipline, $filter, $downloader);
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
}
