<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\Utils;

class EventParser
{
    private XPathDom $xpath;

    public function __construct(XPathDom $xpath)
    {
        $this->xpath = $xpath;
    }

    /**
     * @param DOMNodeList<DOMNode> $events
     * @return array<int, array{rank: int, points: string, name: string, id: int}>
     */
    public function getData(DOMNodeList $events): array
    {
        $result = [];

        foreach ($events as $event) {
            list($rank, $points) = $this->getPilotValues($event);
            list($name, $id) = $this->getEventValues($event);

            $item = [
                'rank' => $rank,
                'points' => $points,
                'name' => $name,
                'id' => $id
            ];

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function getPilotValues(DOMNode $contextNode): array
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="wrapper-point"]')
            ->query($contextNode);

        $error = 'pilot event values';

        $value = Utils::getTextFromNodeList($nodes);
        if ($value === null) {
            throw new \RuntimeException($error);
        }

        $parts = Utils::split('-', $value, 2);
        if ($parts === null) {
            throw new \RuntimeException($error);
        }

        if (!Utils::isNumericText($parts[0])) {
            throw new \RuntimeException('event rank');
        }

        $rank = (int) $parts[0];

        if (!Utils::isNumericText($parts[1])) {
            throw new \RuntimeException('event points');
        }

        $points = $parts[1];

        return [$rank, $points];
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function getEventValues(DOMNode $contextNode): array
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="title-event"]/a')
            ->query($contextNode);


        $name = Utils::getTextFromNodeList($nodes);
        if ($name === null) {
            throw new \RuntimeException('event name');
        }

        $params = Utils::getLinkQueryParams($nodes->item(0));
        if ($params === null) {
            throw new \RuntimeException('event href');
        }

        $id = $params['id'] ?? null;
        if (!is_string($id)) {
            throw new \RuntimeException('event id');
        }

        return [$name, (int) $id];
    }
}
