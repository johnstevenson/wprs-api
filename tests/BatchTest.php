<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use JohnStevenson\JsonWorks\Document;
use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Builder\Config;
use Wprs\Api\Tests\Helpers\MockDownloader;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

class BatchTest extends TestCase
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

    public function testPilots(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html, MockDownloader::MODE_FULL);
        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setRestricted();

        $rankingDates = ['2023-03-01', '2023-04-01'];
        $regionId = $this->config->getRegionId();
        $nationId = $this->config->getNationId();
        $dataSets = $endpoint->getBatch($rankingDates, $regionId, $nationId);

        self::assertCount(2, $dataSets);
        $this->checkData($dataSets[0], $name);
    }

    public function testNations(): void
    {
        $type = System::ENDPOINT_NATIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html, MockDownloader::MODE_FULL);
        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);

        $rankingDates = ['2023-03-01', '2023-04-01'];
        $regionId = $this->config->getRegionId();
        $dataSets = $endpoint->getBatch($rankingDates, $regionId);

        self::assertCount(2, $dataSets);
        $this->checkData($dataSets[0], $name);
    }

    public function testCompetitions(): void
    {
        $type = System::ENDPOINT_COMPETITIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html, MockDownloader::MODE_FULL);
        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);

        $rankingDates = ['2023-03-01', '2023-04-01'];
        $dataSets = $endpoint->getBatch($rankingDates);

        self::assertCount(2, $dataSets);
        $this->checkData($dataSets[0], $name);
    }

    public function testCompetition(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html, MockDownloader::MODE_FULL);
        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);

        $ids = [1, 2];
        $dataSets = $endpoint->getBatch($this->rankingDate, $ids);

        self::assertCount(2, $dataSets);
        $this->checkData($dataSets[0], $name);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function checkData(array $data, string $name): void
    {
        $document = new Document();
        $document->loadData($data);
        $json = $document->toJson(false);

        if ($json === null) {
            self::fail($document->getError());
        }

        $schema = Utils::getSchemaFile($name);
        $document->loadSchema($schema);
        $result = $document->validate();
        self::assertTrue($result, $document->getError());
    }
}
