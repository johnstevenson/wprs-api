<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use JohnStevenson\JsonWorks\Document;
use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Builder\Config;
use Wprs\Api\Tests\Helpers\MockDownloader;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Factory;
use Wprs\Api\Web\RequestInfo;
use Wprs\Api\Web\System;

class RequestInfoTest extends TestCase
{
    private int $discipline;
    private string $rankingDate;
    private Config $config;

    public function setUp(): void
    {
        $this->config = Utils::getConfig();
        $this->discipline = $this->config->getDiscipline();
        $this->rankingDate = $this->config->getRankingDate();
    }

    /**
     * Endpoint tests do not cover request info for http errors
     *
     * @dataProvider endpointProvider
     * @param class-string<object> $type
     */
    public function testHttpErrors(string $type): void
    {
        $name = System::getEndpoint($type);
        $downloader = new MockDownloader(400);
        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);

        try {
            $this->runEndpoint($type, $endpoint);
        } catch (\Exception $e) {
        }

        // @phpstan-ignore-next-line
        $info = $endpoint->getRequestInfo();
        self::assertEquals(0, $info->getCount());
        self::assertEquals(0.0, $info->getTime());
    }

    /**
     * @return array<string, array{0: class-string<object>}>
     */
    public static function endpointProvider(): array
    {
        return [
            'pilots'   => [System::ENDPOINT_PILOTS],
            'nations'    => [System::ENDPOINT_NATIONS],
            'competitions'    => [System::ENDPOINT_COMPETITIONS],
            'competition'    => [System::ENDPOINT_COMPETITION],
        ];
    }

    private function runEndpoint(string $type, object $endpoint): void
    {
        switch ($type) {
            case System::ENDPOINT_PILOTS:
                $regionId = $this->config->getRegionId();
                $nationId = $this->config->getNationId();
                $params = [$this->rankingDate, $regionId, $nationId];
                break;
            case System::ENDPOINT_NATIONS:
                $regionId = $this->config->getRegionId();
                $params = [$this->rankingDate, $regionId];
                break;
            case System::ENDPOINT_COMPETITIONS:
                $params = [$this->rankingDate];
                break;
            case System::ENDPOINT_COMPETITION:
                $compId = $this->config->getCompId();
                $params = [$this->rankingDate, $compId];
                break;
        }

        // @phpstan-ignore-next-line
        $endpoint->getData(...$params);
    }
}
