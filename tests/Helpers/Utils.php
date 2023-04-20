<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers;

use Wprs\Api\Tests\Helpers\Builder\Config;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Nations\Nations;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\System;

class Utils
{
    public static function getHtml(string $endpoint): string
    {
        $folder = self::getFixtureFolder('html');
        $file = sprintf('%s%s%s.html', $folder, DIRECTORY_SEPARATOR, $endpoint);

        $html = file_get_contents($file);

        if ($html === false) {
            throw new \InvalidArgumentException('Unable to open '.$file);
        }

        return $html;
    }

    public static function getHtmlFile(string $endpoint): string
    {
        $folder = self::getFixtureFolder('html');
        $file = sprintf('%s%s%s.html', $folder, DIRECTORY_SEPARATOR, $endpoint);

        return $file;
    }

    public static function getSchemaFile(string $endpoint): string
    {
        if (strpos($endpoint, '-') === false) {
            $folder = self::getSchemaFolder();
        } else {
            $folder = self::getFixtureFolder('schema');
        }

        $file = sprintf('%s%s%s-schema.json', $folder, DIRECTORY_SEPARATOR, $endpoint);

        if (!file_exists($file) || !is_readable($file)) {
            throw new \InvalidArgumentException('Unable to open '.$file);
        }

        return $file;
    }

    public static function getConfig(): Config
    {
        $file = self::getConfigFile();

        $json = file_get_contents($file);

        if ($json === false) {
            throw new \InvalidArgumentException('Unable to open '.$file);
        }

        $data = json_decode($json);

        if (!($data instanceof \stdClass)) {
             throw new \RuntimeException(json_last_error_msg());
        }

        return new Config($data);
    }

    public static function getConfigFile(): string
    {
        $folder = self::getFixtureFolder(null);
        $file = sprintf('%s%sconfig.json', $folder, DIRECTORY_SEPARATOR);

        return $file;
    }

    public static function saveToFile(string $file, string $data): void
    {
        if (file_put_contents($file, $data) === false) {
            throw new \RuntimeException('Unable to save file: '.$file);
        }
    }

    public static function getExpectedUrlCount(Config $config): int
    {
        $itemCount = $config->getPilotsMax();
        $overallCount = $config->getPilotsCount();

        if ($overallCount === 0 || $itemCount === 0) {
            throw new \RuntimeException('Unexpected zero count value');
        }

        return (int) ceil($overallCount / $itemCount);
    }

    private static function getFixtureFolder(?string $name): string
    {
        $path = sprintf('%s/../Fixtures', __DIR__);

        if ($name !== null) {
            $path = sprintf('%s/%s', $path, $name);
        }

        return self::getFolder($path);
    }

    private static function getSchemaFolder(): string
    {
        $path = sprintf('%s/../../res', __DIR__);

        return self::getFolder($path);
    }

    private static function getFolder(string $path): string
    {
        $folder = realpath($path);

        if ($folder === false) {
            throw new \InvalidArgumentException('Folder does not exist: '.$path);
        }

        return $folder;
    }
}