<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use \DOMElement;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\DomUtils;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParserInterface;
use Wprs\Api\Web\Endpoint\XPathDom;


class CompetitionsParser implements ParserInterface
{
    private XPathDom $xpath;

    public function parse(string $html, ?FilterInterface $filter = null): DataCollector
    {
        $this->xpath = new XPathDom($html);

        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        if (0 === $overallCount) {
            return $dataCollector;
        }

        $table = $this->xpath->getElementById('tableMain', $wrapper);
        $this->checkColumnCount($table, 15);
        $rows = $this->getTablesRows($table, $overallCount);

        foreach ($rows as $row) {
            $item = $this->parseRow($row);
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

        $value = DomUtils::getSingleNodeText($nodes, 'competitions count');

        return (int) $value;
    }

    private function checkColumnCount(DomElement $element, int $expected): void
    {
        $nodes = $this->xpath->start()
            ->with('//thead/tr/th')
            ->query(($element));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competitions table columns, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }
    }

    private function getTablesRows(DomElement $element, int $expected): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//tbody/tr[@data-key]')
            ->query(($element));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competitions rows, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }

        return $nodes;
    }

    private function parseRow(DomElement $element): array
    {
        $columns = $this->getColumns($element);
        list($start, $end) = $this->getPeriod($columns->item(0));
        list($name, $id) = $this->getEventValues($columns->item(1));

        $result = [
            'name' => $name,
            'id' => $id,
            'start_date' => $start,
            'end_date' => $end,
        ];

        $key = 'ta';
        $result[$key] = $this->getNumericValue($columns, 3);

        $key = 'pn';
        $result[$key] = $this->getNumericValue($columns, 4);

        $key = 'pq';
        $result[$key] = $this->getNumericValue($columns, 5);

        $key = 'td';
        $result[$key] = $this->getNumericValue($columns, 6);

        $key = 'tasks';
        $result[$key] = (int) $this->getNumericValue($columns, 7);

        $key = 'pilots';
        $result[$key] = (int) $this->getNumericValue($columns, 8);

        $key = 'pilots_last_12_months';
        $result[$key] = (int) $this->getNumericValue($columns, 9);

        $key = 'comps_last_12_months';
        $result[$key] = (int) $this->getNumericValue($columns, 10);

        $key = 'days_since_end';
        $result[$key] = (int) $this->getNumericValue($columns, 11);

        $key = 'last_score';
        $result[$key] = $this->getNumericValue($columns, 12);

        $key = 'winner_score';
        $result[$key] = $this->getNumericValue($columns, 13);

        $key = 'updated';
        $result[$key] = $this->getUpdated($columns->item(14));

        return $result;
    }

    private function getColumns(DomElement $element): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('/td')
            ->query($element);

        return $nodes;
    }

    private function getPeriod(DomElement $element): array
    {
        $childNodes = $element->childNodes;

        // expecting start <br/> end
        if ($childNodes->length !== 3) {
            throw new \RuntimeException('Error getting competitions period');
        }

        $start = $this->getPeriodDate($childNodes, 0,  'competions start');
        $end = $this->getPeriodDate($childNodes, 2, 'competions end ');

        return [$start, $end];
    }

    private function getPeriodDate(DOMNodeList $nodes, int $index, string $type): string
    {
        $date = trim($nodes->item($index)->nodeValue);

        return $this->formatDate($date, $type);
    }

    private function formatDate(string $date, string $type): string
    {
        $error = sprintf('Unexpected %s date value: %s', $type, $date);

        if (!preg_match('/^[A-Z]{1}[a-z]{2}\s\d{1,2},\s\d{4}$/', $date)) {
            throw new \RuntimeException($error);
        }

        $tz = new \DateTimeZone('UTC');
        $date = \DateTimeImmutable::createFromFormat('M j, Y', $date, $tz);

        if (false === $date) {
            throw new \RuntimeException($error);
        }

        return $date->format('Y-m-d');
    }

    private function getEventValues(DOMElement $element)
    {
        $nodes = $this->xpath->start()
            ->with('/a[@class="competition-link"]')
            ->query($element);

        $name = DomUtils::getSingleNodeText($nodes, 'event values');

        // this needs more checking and should be a DomUtils method
        // note getAttribute seems to html decode values
        $url = trim($nodes->item(0)->getAttribute('href'));
        $query = parse_url(html_entity_decode($url), PHP_URL_QUERY);
        parse_str($query, $params);

        $id = $params['id'] ?? null;

        if (null === $id) {
            throw new \RuntimeException('Error getting event id');
        }

        return [$name, (int) $id];
    }

    private function getNumericValue(DOMNodeList $nodes, int $index): string
    {
        $value = trim($nodes->item($index)->nodeValue);

        if (empty($value || !is_numeric($value))) {
            $value = '0.0';
        }

        return $value;
    }

    private function getUpdated(DOMElement $element): string
    {
        // updated values can be empty
        if ($value = trim($element->nodeValue)) {
            return $this->formatDate($value, 'competitions updated');
        }

        return $value;
    }
}
