<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use JohnStevenson\JsonWorks\Document;
use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Builder\Config;
use Wprs\Api\Tests\Helpers\Filter\CompetitionFilter;
use Wprs\Api\Tests\Helpers\Filter\CompetitionsFilter;
use Wprs\Api\Tests\Helpers\Filter\NationsFilter;
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
    private string $rankingDate;
    /**
     * @var array<int, mixed>
     */
    private array $curlOptions;

    private Config $config;

    public function setUp(): void
    {
        $this->config = Utils::getConfig();
        $this->discipline = $this->config->getDiscipline();
        $this->rankingDate = $this->config->getRankingDate();
        $this->curlOptions = [CURLOPT_USERAGENT => 'My-User-Agent/1.0'];
    }

    public function testPilots(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setCurlOptions($this->curlOptions);

        $data = $endpoint->getData($this->rankingDate, $regionId);
        $this->checkData($data, $name);
        self::assertSame($this->curlOptions, $downloader->getCurlOptions());
    }

    public function testPilotsWithFilter(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();
        $filter = new PilotsFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);

        $data = $endpoint->getData($this->rankingDate, $regionId);
        $this->checkData($data, $this->getFilterSchema($name));
    }

    public function testNations(): void
    {
        $type = System::ENDPOINT_NATIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setCurlOptions($this->curlOptions);

        $data = $endpoint->getData($this->rankingDate, $regionId);
        $this->checkData($data, $name);
        self::assertSame($this->curlOptions, $downloader->getCurlOptions());
    }

    public function testNationsWithFilter(): void
    {
        $type = System::ENDPOINT_NATIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();
        $filter = new NationsFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);

        $data = $endpoint->getData($this->rankingDate, $regionId);
        $this->checkData($data, $this->getFilterSchema($name));
    }

    public function testCompetitions(): void
    {
        $type = System::ENDPOINT_COMPETITIONS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setCurlOptions($this->curlOptions);

        $data = $endpoint->getData($this->rankingDate);
        $this->checkData($data, $name);
        self::assertSame($this->curlOptions, $downloader->getCurlOptions());
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
        $this->checkData($data, $this->getFilterSchema($name));
    }

    public function testCompetition(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $compId = $this->config->getCompId();

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setCurlOptions($this->curlOptions);

        $data = $endpoint->getData($this->rankingDate, $compId);
        $this->checkData($data, $name);
        self::assertSame($this->curlOptions, $downloader->getCurlOptions());
    }

    public function testCompetitionWithFilter(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $compId = $this->config->getCompId();
        $filter = new CompetitionFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);

        $data = $endpoint->getData($this->rankingDate, $compId);
        $this->checkData($data, $this->getFilterSchema($name));
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

        $discipline = $document->getValue('/meta/discipline');
        self::assertEquals($this->config->getActivity(), $discipline);

        $date = $document->getValue('/meta/ranking_date');
        self::assertEquals($this->rankingDate, $date);

        $count = $document->getValue('/meta/count');
        $items = $document->getValue('/data/items');
        self::assertIsArray($items);
        self::assertEquals($count, count($items));


        if (in_array($name, ['competition', 'competition-filter'], true)) {
            $id = $document->getValue('/data/details/id');
            $compId = $this->config->getCompId();
            self::assertEquals($compId, $id);
        }

        if ($name === 'competitions-filter') {
            foreach ($items as $item) {
                if ($item->tasks === 0) {
                    self::fail('tasks must not be zero');
                }
            }
        }
    }

    private function getFilterSchema(string $name): string
    {
        return $name.'-filter';
    }
}
