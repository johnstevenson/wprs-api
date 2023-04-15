<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers;

use Wprs\Api\Tests\Helpers\Builder\BuildConfig;
use Wprs\Api\Web\System;

class Utils
{
    public static function getHtml(string $endpoint): string
    {
        $folder = self::getFixtureFolder('html');
        $file = sprintf('%s%s%s.html', $folder, DIRECTORY_SEPARATOR, $endpoint);

        $html = file_get_contents($file);

        if ($html === false) {
            $message = 'Test not run, unable to open '.$file;
            throw new \InvalidArgumentException($message);
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
            $message = 'Test not run, unable to open '.$file;
            throw new \InvalidArgumentException($message);
        }

        return $file;
    }

    public static function getConfig(): BuildConfig
    {
        $file = self::getConfigFile();

        $json = file_get_contents($file);

        if ($json === false) {
            $message = 'Test not run, unable to open '.$file;
            throw new \InvalidArgumentException($message);
        }

        $config = new BuildConfig($json);

        return $config;
    }

    public static function getConfigFile(): string
    {
        $folder = self::getFixtureFolder(null);
        $file = sprintf('%s%sconfig.json', $folder, DIRECTORY_SEPARATOR);

        return $file;
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
            $message = 'Test not run, folder does not exists: '.$path;
            throw new \InvalidArgumentException($message);
        }

        return $folder;
    }
}