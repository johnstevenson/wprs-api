<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use JohnStevenson\JsonWorks\Document;
use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Filter\CompetitionFilter;
use Wprs\Api\Tests\Helpers\Filter\CompetitionsFilter;
use Wprs\Api\Tests\Helpers\Filter\PilotsFilter;
use Wprs\Api\Tests\Helpers\MockDownloader;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

class EndpointTest extends TestCase
{
    private int $discipline;
    private int $regionId;
    private int $compId;
    private string $rankingDate;

    public function setUp(): void
    {
        $config = Utils::getConfig();
        $this->discipline = $config->getDiscipline();
        $this->rankingDate = $config->getRankingDate();
        $this->regionId = $config->getRegionId();
        $this->compId = $config->getCompId();
    }

    public function testPilots(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate, $this->regionId);
        $this->checkData($data, $name);
    }

    public function testPilotsWithFilter(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $filter = new PilotsFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);
        $data = $endpoint->getData($this->rankingDate, $this->regionId);
        $this->checkData($data, $name.'-filter');
    }

    public function testCompetitions(): void
    {
        $type = System::ENDPOINT_COMPETITIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate);
        $this->checkData($data, $name);
    }

    public function testCompetitionsWithFilter(): void
    {
        $type = System::ENDPOINT_COMPETITIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $filter = new CompetitionsFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);
        $data = $endpoint->getData($this->rankingDate);
        $this->checkData($data, $name.'-filter');
    }

    public function testCompetition(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate, $this->compId);
        $this->checkData($data, $name);
    }

    public function testCompetitionWithFilter(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $filter = new CompetitionFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);
        $data = $endpoint->getData($this->rankingDate, $this->compId);
        $this->checkData($data, $name.'-filter');
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function checkData(array $data, string $name): void
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

        $date = $document->getValue('/meta/ranking_date');
        self::assertEquals($this->rankingDate, $date);

        $count = $document->getValue('/meta/count');
        $items = $document->getValue('/data/items');
        self::assertIsArray($items);
        self::assertEquals($count, count($items));

        if (in_array($name, ['competition', 'competition-filter'], true)) {
            $id = $document->getValue('/data/details/id');
            self::assertEquals($this->compId, $id);
        }

        if ($name === 'competitions-filter') {
            foreach ($items as $item) {
                if ($item->tasks === 0) {
                    self::fail('tasks must not be zero');
                }
            }
        }
    }
}
