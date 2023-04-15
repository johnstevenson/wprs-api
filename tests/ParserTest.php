<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use PHPUnit\Framework\TestCase;
use Wprs\Api\Tests\Helpers\Filter\CompetitionFilter;
use Wprs\Api\Tests\Helpers\Filter\CompetitionsFilter;
use Wprs\Api\Tests\Helpers\Filter\PilotsFilter;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\Competitions\CompetitionsParser;
use Wprs\Api\Web\Endpoint\Competition\CompetitionParser;
use Wprs\Api\Web\Endpoint\Pilots\PilotsParser;

class ParserTest extends TestCase
{
    public function testPilots(): void
    {
        $html = Utils::getHtml('pilots');
        $parser = new PilotsParser();
        $dataCollector = $parser->parse($html);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);
    }

    public function testPilotsWithFilter(): void
    {
        $html = Utils::getHtml('pilots');
        $parser = new PilotsParser();
        $filter = new PilotsFilter();
        $dataCollector = $parser->parse($html, $filter);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);

        $item = $items[0];
        self::assertCount(6, $item);

        $expected = ['id', 'name', 'gender', 'points', 'rank', 'rworld'];
        $this->checkExpectedProperties($expected, $item);
    }

    public function testCompetitions(): void
    {
        $html = Utils::getHtml('competitions');
        $parser = new CompetitionsParser();
        $dataCollector = $parser->parse($html);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);
    }

    public function testCompetitionsWithFilter(): void
    {
        $html = Utils::getHtml('competitions');
        $parser = new CompetitionsParser();
        $filter = new CompetitionsFilter();
        $dataCollector = $parser->parse($html, $filter);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);

        $item = $items[0];
        self::assertCount(7, $item);

        $expected = ['start_date', 'end_date', 'id', 'name', 'tasks', 'pilots', 'updated'];
        $this->checkExpectedProperties($expected, $item);

        foreach ($items as $testItem) {
            if ($testItem['tasks'] === 0) {
                self::fail('tasks must not be zero');
            }
        }
    }

    public function testCompetition(): void
    {
        $html = Utils::getHtml('competition');
        $parser = new CompetitionParser();
        $dataCollector = $parser->parse($html);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);
    }

    public function testCompetitionWithFilter(): void
    {
        $html = Utils::getHtml('competition');
        $parser = new CompetitionParser();
        $filter = new CompetitionFilter();
        $dataCollector = $parser->parse($html, $filter);

        $count = $dataCollector->getItemCount();
        $items = $dataCollector->getItems();
        self::assertCount($count, $items);

        $item = $items[0];
        self::assertCount(3, $item);

        $expected = ['civl_id', 'name', 'points'];
        $this->checkExpectedProperties($expected, $item);
    }

    /**
     * @param array<string> $keys
     * @param array<string, mixed> $item
     */
    protected function checkExpectedProperties(array $keys, array $item): void
    {
        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $item, $key.' should exist');
        }
    }
}
