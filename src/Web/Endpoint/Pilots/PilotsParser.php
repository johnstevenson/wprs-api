<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

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
class PilotsParser extends ParserManager
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

        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        if ($overallCount === 0) {
            return $dataCollector;
        }

        $list = $this->xpath->getElementById('w0', $wrapper);
        if ($list === null) {
            throw new \RuntimeException('id=w0');
        }

        $pilotNodes = $this->getPilotList($list);

        foreach ($pilotNodes as $node) {
            $item = $this->parsePilotRow($node);
            $dataCollector->add($item, $this->filter);
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

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('competitions count');
        }

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
        $columns = $this->getColumns($contextNode, 6, 'pilot row');

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

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('civl_id');
        }

        return (int)$value;
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
            ->with('//div[@class="main-ranking"]')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException('rank');
        }

        return (int)$value;
    }

    /**
     * @return array<int, non-empty-array<string, int>>
     */
    private function getOtherRankings(?DOMNode $contextNode): array
    {
        if ($contextNode === null) {
            throw new \RuntimeException('other rankings div');
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="scoring-category"]')
            ->query($contextNode);

        $result = [];
        $error= 'xrank item';

        foreach ($nodes as $node) {
            $value = Utils::getNodeText($node);
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

            $result[] = [$rankType => (int) $rank];
        }

        return $result;
    }

    private function getPilotName(?DOMNode $contextNode): string
    {
        $error = 'pilot name';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//div[@class="pilot-id"]/../a')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException($error);
        }

        return $value;
    }

    private function getPilotGender(?DOMNode $contextNode): string
    {
        $value = Utils::getNodeText($contextNode);
        if ($value === null) {
            throw new \RuntimeException('gender');
        }

        return $value;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function getNation(?DOMNode $contextNode): array
    {
        $error = 'nation details';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $nodes = $this->xpath->start()
            ->with('//a[@data-nation-id]')
            ->query($contextNode);

        // get nation name
        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException($error);
        }

        $nation = $value;

        // get nation id
        $value = Utils::getAttribute($nodes->item(0), 'data-nation-id');
        if ($value === null) {
            throw new \RuntimeException($error);
        }

        if (!Utils::isNumericText($value)) {
            throw new \RuntimeException('nation_id');
        }

        $nationId = (int) $value;

        return [$nation, $nationId];
    }

    private function getPilotPoints(?DOMNode $contextNode): string
    {
        $error = 'points';

        $value = Utils::getNodeText($contextNode);
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
    private function getPilotEvents(?DOMNode $contextNode): DOMNodeList
    {
        $error = 'pilot events.';

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
