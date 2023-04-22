<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Nations;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\EventParser;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParserManager;
use Wprs\Api\Web\Endpoint\Utils;
use Wprs\Api\Web\Endpoint\XPathDom;

/**
 * @phpstan-import-type apiItem from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class NationsParser extends ParserManager
{
    private EventParser $eventParser;

    protected function run(): DataCollector
    {
        $this->eventParser = new EventParser($this->xpath);

        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        if ($wrapper === null) {
            throw new \RuntimeException('id=rankingTableWrapper');
        }

        $updated = $this->getRankingUpdated($wrapper);

        list($overallCount, $countWorld) = $this->getOverallCounts($wrapper);
        $dataCollector = new DataCollector($overallCount, $updated);

        if ($overallCount === 0) {
            return $dataCollector;
        }

        $details = ['count_ww' => $countWorld];
        $dataCollector->addExtra('details', $details);

        $list = $this->xpath->getElementById('w0', $wrapper);
        if ($list === null) {
            throw new \RuntimeException('id=w0');
        }

        $nationNodes = $this->getNationList($list);

        foreach ($nationNodes as $node) {
            $item = $this->parseNationRow($node);
            $dataCollector->addItem($item, null, $this->filter);
        }

        return $dataCollector;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function getOverallCounts(DOMNode $contextNode): array
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="table-title-row"]/div')
            ->withClassContains('count-pilots')
            ->with('/span[@class="count"]')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('nations count');
        }

        $count = (int) $value;
        $countWorld = $count;

        // get the text outside the span
        $nodes = $this->xpath->with('/following-sibling::text()')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null || Utils::isEmptyString($value)) {
            throw new \RuntimeException('nations count_ww');
        }

        // (worldwide 86)
        $pattern = '/^\\(\\s*worldwide\\s*(\\d+)\\s*\\)$/';
        $valid = (bool) preg_match($pattern, $value, $matches);

        if (!$valid) {
            throw new \RuntimeException('nations count_ww');
        }

        $countWorld = (int) $matches[1];

        return [$count, $countWorld];
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getNationList(DOMNode $contextNode): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div')
            ->withClassContainsList(['cms__table__row', 'pilot-item'])
            ->query($contextNode);

        return $nodes;
    }

    /**
     * @phpstan-return apiItem
     */
    private function parseNationRow(DOMNode $contextNode): array
    {
        $nationId = $this->getNationId($contextNode);
        $columns = $this->getColumns($contextNode, 5, 'nation row');

        $rank = $this->getMainRanking($columns->item(0));
        $rankWorld = $this->getWorldRanking($columns->item(0));
        $nation = $this->getNationName($columns->item(1));
        $pilots = $this->getPilots($columns->item(2));
        $points = $this->getNationPoints($columns->item(3));
        $events = $this->getNationScores($columns->item(4));
        $scores = $this->eventParser->getNationData($events);

        $item = [
            'nation' => $nation,
            'nation_id' => $nationId,
            'rank' => $rank,
            'rank_ww' => $rankWorld,
            'pilots' => $pilots,
            'points' => $points,
            'scores' => $scores,
        ];

        return $item;
    }

    private function getNationId(DOMNode $contextNode): int
    {
        $nodes = $this->xpath->start()
            ->with('//@data-id')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('nation_id');
        }

        return (int) $value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getColumns(DOMNode $contextNode, int $expected, string $name): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="row"]/div[starts-with(@class,"col-")]')
            ->query($contextNode);

        if ($nodes->length !== $expected) {
            $message = Utils::getCountMessage($expected, $name, 'columns', $nodes->length);
            throw new \RuntimeException($message);
        }

        return $nodes;
    }

    private function getMainRanking(?DOMNode $contextNode): int
    {
        if ($contextNode === null) {
            throw new \RuntimeException('main ranking div');
        }

        $nodes = $this->xpath->start()
            ->with('/text()')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('rank');
        }

        return (int) $value;
    }

    private function getWorldRanking(?DOMNode $contextNode): int
    {
        if ($contextNode === null) {
            throw new \RuntimeException('main ranking div');
        }

        $error = 'nation rank_ww';

        $nodes = $this->xpath->start()
            ->with('/div/text()')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException($error);
        }

        $parts = Utils::split(':', $value, 2);
        if ($parts === null) {
            throw new \RuntimeException($error);
        }

        list($rankType, $rank) = $parts;

        if (!Utils::isNumericText($rank)) {
            throw new \RuntimeException($error);
        }

        return (int) $rank;
    }

    private function getNationName(?DOMNode $contextNode): string
    {
        $error = 'nation';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('/a')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null || Utils::isEmptyString($value)) {
            throw new \RuntimeException($error);
        }

        return $value;
    }

    private function getPilots(?DOMNode $contextNode): int
    {
        if ($contextNode === null) {
            throw new \RuntimeException('pilots div');
        }

        $nodes = $this->xpath->start()
            ->with('/text()')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('pilots');
        }

        return (int) $value;
    }

    private function getNationPoints(?DOMNode $contextNode): string
    {
        $error = 'points';

        $nodes = $this->xpath->start()
            ->with('/a')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);

        if ($value === null) {
            throw new \RuntimeException($error);
        }

        if (!Utils::isNumericText($value)) {
            throw new \RuntimeException($error);
        }

        return $value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getNationScores(?DOMNode $contextNode): DOMNodeList
    {
        $error = 'nation scores.';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div')
            ->withClassContains('event-wrapper')
            ->query($contextNode);

        if ($nodes->length < 1 || $nodes->length > 4) {
            throw new \RuntimeException($error);
        }

        return $nodes;
    }
}
