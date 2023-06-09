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
        $nationId = $this->config->getNationId();

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $endpoint->setCurlOptions($this->curlOptions);

        $data = $endpoint->getData($this->rankingDate, $regionId, $nationId);
        $this->checkData($data, $name);
        self::assertSame($this->curlOptions, $downloader->getCurlOptions());

        $info = $endpoint->getRequestInfo();
        self::assertGreaterThan(0, $info->getCount());
        self::assertGreaterThan(0.0, $info->getTime());

        $expectedRequests = Utils::getExpectedUrlCount($this->config);
        self::assertEquals($expectedRequests, $downloader->getUrlCount());
    }

    public function testPilotsWithFilter(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();
        $nationId = $this->config->getNationId();
        $filter = new PilotsFilter();

        $endpoint = Factory::createEndpoint($type, $this->discipline, $filter, $downloader);

        $data = $endpoint->getData($this->rankingDate, $regionId, $nationId);
        $this->checkData($data, $this->getFilterName($name));
    }

    public function testPilotsHttpError(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $downloader = new MockDownloader(400);
        $regionId = $this->config->getRegionId();
        $nationId = $this->config->getNationId();

        self::expectException(\Wprs\Api\Web\WprsException::class);
        self::expectExceptionMessage('http status 400');

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate, $regionId, $nationId);
    }

    public function testPilotsGetCount(): void
    {
        $type = System::ENDPOINT_PILOTS;
        $name = System::getEndpoint($type);

        $html = Utils::getHtml($name);
        $downloader = new MockDownloader($html);
        $regionId = $this->config->getRegionId();
        $nationId = $this->config->getNationId();

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $count = $endpoint->getCount($this->rankingDate, $regionId, $nationId);
        self::assertEquals($count, $this->config->getPilotsCount());
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

        $info = $endpoint->getRequestInfo();
        self::assertGreaterThan(0, $info->getCount());
        self::assertGreaterThan(0.0, $info->getTime());
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
        $this->checkData($data, $this->getFilterName($name));
    }

    public function testNationsHttpError(): void
    {
        $type = System::ENDPOINT_NATIONS;
        $downloader = new MockDownloader(400);
        $regionId = $this->config->getRegionId();

        self::expectException(\Wprs\Api\Web\WprsException::class);
        self::expectExceptionMessage('http status 400');

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate, $regionId);
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

        $info = $endpoint->getRequestInfo();
        self::assertGreaterThan(0, $info->getCount());
        self::assertGreaterThan(0.0, $info->getTime());
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
        $this->checkData($data, $this->getFilterName($name));
    }

    public function testCompetitionsHttpError(): void
    {
        $type = System::ENDPOINT_COMPETITIONS;
        $downloader = new MockDownloader(400);

        self::expectException(\Wprs\Api\Web\WprsException::class);
        self::expectExceptionMessage('http status 400');

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate);
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

        $info = $endpoint->getRequestInfo();
        self::assertGreaterThan(0, $info->getCount());
        self::assertGreaterThan(0.0, $info->getTime());
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
        $this->checkData($data, $this->getFilterName($name));
    }

    public function testCompetitionHttpError(): void
    {
        $type = System::ENDPOINT_COMPETITION;
        $downloader = new MockDownloader(400);
        $compId = $this->config->getCompId();

        self::expectException(\Wprs\Api\Web\WprsException::class);
        self::expectExceptionMessage('http status 400');

        $endpoint = Factory::createEndpoint($type, $this->discipline, null, $downloader);
        $data = $endpoint->getData($this->rankingDate, $compId);
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

        $updated = $document->getValue('/meta/updated');
        self::assertEquals($this->config->getUpdated(), $updated);

        $count = $document->getValue('/meta/count');
        $items = $document->getValue('/data/items');
        self::assertIsArray($items);
        self::assertEquals($count, count($items));

        if (in_array($name, ['competition', $this->getFilterName('competition')], true)) {
            $id = $document->getValue('/data/details/id');
            $compId = $this->config->getCompId();
            self::assertEquals($compId, $id);
        }

        if ($name === $this->getFilterName('competitions')) {
            foreach ($items as $item) {
                if ($item->tasks === 0) {
                    self::fail('tasks must not be zero');
                }
            }
        }
    }

    private function getFilterName(string $name): string
    {
        return $name.'-filter';
    }
}
