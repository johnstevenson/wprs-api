<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\DomUtils;
use Wprs\Api\Web\Endpoint\EventParser;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParserInterface;
use Wprs\Api\Web\Endpoint\XPathDom;

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

    private function getOverallCount(DOMNode $contextNode): int
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="table-title-row"]/div')
            ->withClassContains('count-pilots')
            ->with('/span[@class="count"]')
            ->query($contextNode);

        $value = DomUtils::getSingleNodeText($nodes, 'pilot count');

        return (int) $value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getPilotList(DOMNode $contextNode): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div')
            ->withClassContainsList(['cms__table__row', 'pilot-item'])
            ->query($contextNode);

        return $nodes;
    }

    /**
     * @return non-empty-array<string, string>
     */
    private function parsePilotRow(DOMNode $contextNode): array
    {
        $civlId = $this->getCivlId($contextNode);
        $columns = $this->getColumns($contextNode, 6);

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

    private function getCivlId(DOMNode $contextNode): int
    {
        $nodes = $this->xpath->start()
            ->with('//@data-id')
            ->query($contextNode);

        if ($nodes->length !== 1) {
            throw new \RuntimeException('Cannot find pilot civl id');
        }

        $value = trim($nodes->item(0)->nodeValue);

        return (int)$value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getColumns(DOMNode $contextNode, int $expected): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="row"]/div[starts-with(@class,"col-")]')
            ->query($contextNode);

        if ($nodes->length !== $expected) {
            $format = 'expected %d pilot row columns, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }

        return $nodes;
    }

    private function getMainRanking(DOMNode $column): int
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

    /**
     * @return array<int, non-empty-array<string, string>>
     */
    private function getOtherRankings(DOMNode $column): array
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

    private function getPilotName(DOMNode $column): string
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

    private function getPilotGender(DOMNode $column): string
    {
        $value = DomUtils::getElementText($column, 'pilot gender');

        return $value;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function getNation(DOMNode $column): array
    {
        $nodes = $this->xpath->start()
            ->with('//a[@data-nation-id]')
            ->query($column);

        $nation = DomUtils::getSingleNodeText($nodes, 'nation details');

        $value = DomUtils::getAttribute($nodes->item(0), 'data-nation-id', 'nation id');
        $nationId = (int) $value;

        return [$nation, $nationId];
    }

    private function getPilotPoints(DOMNode $column): string
    {
        $value = DomUtils::getElementText($column, 'pilot points');

        return $value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getPilotEvents(DOMNode $column): DOMNodeList
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
