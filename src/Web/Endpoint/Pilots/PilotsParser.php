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

/**
 * @phpstan-import-type apiItem from \Wprs\Api\Web\Endpoint\ApiOutput
 */
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

        if ($wrapper === null) {
            throw new \RuntimeException('Error getting pilots rankingTableWrapper.');
        }

        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        $list = $this->xpath->getElementById('w0', $wrapper);

        if ($list === null) {
            throw new \RuntimeException('Error getting pilots w0.');
        }

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
     * @phpstan-return apiItem
     */
    private function parsePilotRow(DOMNode $contextNode): array
    {
        $civlId = $this->getCivlId($contextNode);
        $columns = $this->getColumns($contextNode, 6);

        if ($columns->count() !== 6) {
            throw new \RuntimeException('error.');
        }

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

        $value = DomUtils::getSingleNodeText($nodes, 'Cannot find pilot civl id');

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

    private function getMainRanking(?DOMNode $contextNode): int
    {
        $error = 'Cannot find pilot main ranking';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="main-ranking"]')
            ->query($contextNode);

        $value = DomUtils::getSingleNodeText($nodes, $error);

        return (int)$value;
    }

    /**
     * @return array<int, non-empty-array<string, string>>
     */
    private function getOtherRankings(?DOMNode $contextNode): array
    {
        $error = 'Cannot find pilot other rankings';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="scoring-category"]')
            ->query($contextNode);

        $result = [];
        $type = 'pilot other rankings';

        foreach ($nodes as $node) {
            $value = DomUtils::getElementText($node, $type);
            $parts = DomUtils::split(':', $value, 2, $type);
            $result[] = [$parts[0] => $parts[1]];
        }

        return $result;
    }

    private function getPilotName(?DOMNode $contextNode): string
    {
        $error = 'Cannot find pilot pilot main name';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="pilot-id"]/../a')
            ->query($contextNode);

        $value = DomUtils::getSingleNodeText($nodes, $error);

        return $value;
    }

    private function getPilotGender(?DOMNode $contextNode): string
    {
        if ($contextNode === null) {
            throw new \RuntimeException('Cannot find pilot gender.');
        }

        $value = DomUtils::getElementText($contextNode, 'pilot gender');

        return $value;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function getNation(?DOMNode $contextNode): array
    {
        if ($contextNode === null) {
            throw new \RuntimeException('Cannot find pilot nation.');
        }

        $nodes = $this->xpath->start()
            ->with('//a[@data-nation-id]')
            ->query($contextNode);

        $nation = DomUtils::getSingleNodeText($nodes, 'nation details');

        $value = DomUtils::getAttribute($nodes->item(0), 'data-nation-id', 'nation id');
        $nationId = (int) $value;

        return [$nation, $nationId];
    }

    private function getPilotPoints(?DOMNode $contextNode): string
    {
        if ($contextNode === null) {
            throw new \RuntimeException('Cannot find pilot points.');
        }

        $value = DomUtils::getElementText($contextNode, 'pilot points');

        return $value;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getPilotEvents(?DOMNode $contextNode): DOMNodeList
    {
        $error = 'Cannot find pilot events.';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="event-wrapper"]')
            ->query($contextNode);

        if ($nodes->length < 1 || $nodes->length > 4) {
            throw new \RuntimeException($error);
        }

        return $nodes;
    }
}
