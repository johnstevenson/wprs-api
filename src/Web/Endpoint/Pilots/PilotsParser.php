<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use \DOMElement;
use \DOMNodeList;
use Wprs\Api\Web\DataCollector;
use Wprs\Api\Web\DomUtils;
use Wprs\Api\Web\FilterInterface;
use Wprs\Api\Web\Endpoint\EventParser;
use Wprs\Api\Web\ParserInterface;
use Wprs\Api\Web\XPathDom;

class PilotsParser implements ParserInterface
{
    private XPathDom $xpath;
    private EventParser $eventParser;

    public function parse(string $html, ?FilterInterface $filter = null): DataCollector
    {
        $this->xpath = new XPathDom($html);
        $this->eventParser = new EventParser($this->xpath);

        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        $list = $this->xpath->getElementById('w0', $wrapper);
        $pilotNodes = $this->getPilotList($list);

        foreach ($pilotNodes as $node) {
            $item = $this->parsePilotRow($node);
            $dataCollector->add($item, $filter);
        }

        return $dataCollector;
    }

    private function getOverallCount(DOMElement $element): int
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="table-title-row"]/div')
            ->withClassContains('count-pilots')
            ->with('/span[@class="count"]')
            ->query($element);

        $value = DomUtils::getSingleNodeText($nodes, 'pilot count');

        return (int) $value;
    }

    private function getPilotList(DOMElement $element): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div')
            ->withClassContainsList(['cms__table__row', 'pilot-item'])
            ->query($element);

        return $nodes;
    }

    private function parsePilotRow($element): array
    {
        $civlId = $this->getCivlId($element);
        $columns = $this->getColumns($element, 6);

        $rank = $this->getMainRanking($columns->item(0));
        $xranks = $this->getOtherRankings($columns->item(0));
        $name = $this->getPilotName($columns->item(1));
        $gender = $this->getPilotGender($columns->item(2));
        list($nation, $nationId) = $this->getNation($columns->item(3));
        $points = $this->getPilotPoints($columns->item(4));
        $events = $this->getPilotEvents($columns->item(5));
        $comps = $this->eventParser->getData($events);

        $item = [
            'civl_id' => $civlId,
            'name' => $name,
            'gender' => $gender,
            'nation' => $nation,
            'nation_id' => $nationId,
            'rank' => $rank,
            'xranks' => $xranks,
            'points' => $points,
            'comps' => $comps,
        ];

        return $item;
    }

    private function getCivlId(DOMElement $element): int
    {
        $nodes = $this->xpath->start()
            ->with('//@data-id')
            ->query($element);

        if ($nodes->length !== 1) {
            throw new \RuntimeException('Cannot find pilot civl id');
        }

        $value = trim($nodes->item(0)->nodeValue);

        return (int)$value;
    }

    private function getColumns(DOMElement $element, int $expected): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="row"]/div[starts-with(@class,"col-")]')
            ->query($element);

        if ($nodes->length !== $expected) {
            $format = 'expected %d pilot row columns, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }

        return $nodes;
    }

    private function getMainRanking(DOMElement $column): int
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="main-ranking"]')
            ->query($column);

        if ($nodes->length !== 1) {
            throw new \RuntimeException('Cannot find pilot main ranking');
        }

        $value = trim($nodes->item(0)->nodeValue);

        return (int)$value;
    }

    private function getOtherRankings(DOMElement $column): array
    {
        $result = [];

        $nodes = $this->xpath->start()
            ->with('//div[@class="scoring-category"]')
            ->query($column);

        $type = 'pilot other rankings';

        foreach ($nodes as $node) {
            $value = DomUtils::getElementText($node, $type);
            $parts = DomUtils::split(':', $value, 2, $type);
            $result[] = [$parts[0] => $parts[1]];
        }

        return $result;
    }

    private function getPilotName(DOMElement $column): string
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="pilot-id"]/../a')
            ->query($column);

        if ($nodes->length !== 1) {
            throw new \RuntimeException('Cannot find pilot main name');
        }

        $value = trim($nodes->item(0)->nodeValue);

        return $value;
    }

    private function getPilotGender(DOMElement $column): string
    {
        $value = DomUtils::getElementText($column, 'pilot gender');

        return $value;
    }

    private function getNation(DOMElement $column): array
    {
        $nodes = $this->xpath->start()
            ->with('//a[@data-nation-id]')
            ->query($column);

        $nation = DomUtils::getSingleNodeText($nodes, 'nation details');

        $value = trim($nodes->item(0)->getAttribute('data-nation-id'));
        $nationId = (int) $value;

        return [$nation, $nationId];
    }

    private function getPilotPoints(DOMElement $column): string
    {
        $value = DomUtils::getElementText($column, 'pilot points');

        return $value;
    }

    private function getPilotEvents(DOMElement $column): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="event-wrapper"]')
            ->query($column);

        if ($nodes->length < 1 || $nodes->length > 4) {
            throw new \RuntimeException('Error getting pilot events');
        }

        return $nodes;
    }
}
