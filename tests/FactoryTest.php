<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Filter\PilotsFilter;
use Wprs\Api\Tests\Helpers\MockDownloader;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

class FactoryTest extends TestCase
{
    /**
      * @dataProvider endpointProvider
      * @param class-string $class
      */
    public function testFactoryValid(string $class): void
    {
        $discipline = System::DISCIPLINE_HG_CLASS_1;
        $endpoint = Factory::createEndpoint($class, $discipline);

        self::assertInstanceOf($class, $endpoint);
    }

    public function testFactoryInvalid(): void
    {
        /** @phpstan-var class-string $class */
        $class = 'NewEndpoint';
        $discipline = System::DISCIPLINE_HG_CLASS_1;

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage($class);
        $endpoint = Factory::createEndpoint($class, $discipline);
    }

    public function testFactoryParams(): void
    {
        $class = Pilots::class;
        $discipline = System::DISCIPLINE_HG_CLASS_1;

        // tests that all params can be used
        $filter = new PilotsFilter();
        $downloader = new MockDownloader(400);
        $endpoint = Factory::createEndpoint($class, $discipline, $filter, $downloader);

        $reflection = (new \ReflectionClass($endpoint))->getParentClass();

        if ($reflection === false) {
            self::fail('reflection failed');
        }

        $property = $reflection->getProperty('filter');
        $property->setAccessible(true);
        $instance = $property->getValue($endpoint);
        self::assertEquals($filter, $instance);

        $property = $reflection->getProperty('downloader');
        $property->setAccessible(true);
        $instance = $property->getValue($endpoint);
        self::assertEquals($downloader, $instance);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function endpointProvider(): array
    {
        return [
            'pilots'        => [Pilots::class],
            'competitions'  => [Competitions::class],
            'competition'   => [Competition::class],
        ];
    }
}