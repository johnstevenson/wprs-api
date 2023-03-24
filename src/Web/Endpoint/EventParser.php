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

    private function getPilotValues(DOMNode $contextNode)
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

    private function getEventValues(DOMNode $contextNode)
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="title-event"]/a')
            ->query($contextNode);

        $name = DomUtils::getSingleNodeText($nodes, 'event values');

        // this needs more checking and should be a DomUtils method
        // note getAttribute seems to html decode values

        $url = DomUtils::getAttribute($nodes->item(0), 'href', 'event values');

        $query = parse_url(html_entity_decode($url), PHP_URL_QUERY);
        parse_str($query, $params);

        $id = $params['id'] ?? null;

        if (null === $id) {
            throw new \RuntimeException('Error getting event id');
        }

        return [$name, (int) $id];
    }
}
