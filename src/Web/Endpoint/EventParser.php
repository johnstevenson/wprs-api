<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;

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

        $type = 'pilot event values';
        $value = DomUtils::getSingleNodeText($nodes, $type);
        $parts = DomUtils::split('-', $value, 2, $type);

        $rank = (int) $parts[0];
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

        $name = DomUtils::getSingleNodeText($nodes, 'event values');

        // this needs more checking and should be a DomUtils method
        // note getAttribute seems to html decode values

        $url = DomUtils::getAttribute($nodes->item(0), 'href', 'event values');

        $query = parse_url(html_entity_decode($url), PHP_URL_QUERY);

        if (!is_string($query)) {
            throw new \RuntimeException('Error getting event id');
        }

        parse_str($query, $params);

        $id = $params['id'] ?? null;

        if (null === $id) {
            throw new \RuntimeException('Error getting event id');
        }

        return [$name, (int) $id];
    }
}
