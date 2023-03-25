<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use \DOMElement;
use \DOMNode;
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

        if ($wrapper === null) {
            throw new \RuntimeException('Error getting competitions rankingTableWrapper.');
        }

        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        if (0 === $overallCount) {
            return $dataCollector;
        }

        $table = $this->xpath->getElementById('tableMain', $wrapper);

        if ($table === null) {
            throw new \RuntimeException('Error getting competitions tableMain.');
        }

        $this->checkColumnCount($table, 15);
        $rows = $this->getTablesRows($table, $overallCount);

        foreach ($rows as $row) {
            $item = $this->parseRow($row);
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

        $value = DomUtils::getSingleNodeText($nodes, 'competitions count');

        return (int) $value;
    }

    private function checkColumnCount(DOMNode $contextNode, int $expected): void
    {
        $nodes = $this->xpath->start()
            ->with('//thead/tr/th')
            ->query(($contextNode));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competitions table columns, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getTablesRows(DOMNode $contextNode, int $expected): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//tbody/tr[@data-key]')
            ->query(($contextNode));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competitions rows, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }

        return $nodes;
    }

    /**
     * @return non-empty-array<string, string|int>
     */
    private function parseRow(DOMNode $contextNode): array
    {
        $columns = $this->getColumns($contextNode);
        list($start, $end) = $this->getPeriod($columns->item(0));
        list($name, $id) = $this->getEventValues($columns->item(1));

        $result = [
            'name' => $name,
            'id' => $id,
            'start_date' => $start,
            'end_date' => $end,
        ];

        $key = 'ta';
        $result[$key] = $this->getNumericValue($columns, 3, $key);

        $key = 'pn';
        $result[$key] = $this->getNumericValue($columns, 4, $key);

        $key = 'pq';
        $result[$key] = $this->getNumericValue($columns, 5, $key);

        $key = 'td';
        $result[$key] = $this->getNumericValue($columns, 6, $key);

        $key = 'tasks';
        $result[$key] = (int) $this->getNumericValue($columns, 7, $key);

        $key = 'pilots';
        $result[$key] = (int) $this->getNumericValue($columns, 8, $key);

        $key = 'pilots_last_12_months';
        $result[$key] = (int) $this->getNumericValue($columns, 9, $key);

        $key = 'comps_last_12_months';
        $result[$key] = (int) $this->getNumericValue($columns, 10, $key);

        $key = 'days_since_end';
        $result[$key] = (int) $this->getNumericValue($columns, 11, $key);

        $key = 'last_score';
        $result[$key] = $this->getNumericValue($columns, 12, $key);

        $key = 'winner_score';
        $result[$key] = $this->getNumericValue($columns, 13, $key);

        $key = 'updated';
        $result[$key] = $this->getUpdated($columns->item(14), $key);

        return $result;
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getColumns(DOMNode $contextNode): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('/td')
            ->query($contextNode);

        return $nodes;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getPeriod(?DOMNode $node): array
    {
        $error = 'Error getting competitions period';

        if ($node === null) {
            throw new \RuntimeException($error);
        }

        $childNodes = $node->childNodes;

        // expecting start <br/> end
        if ($childNodes->length !== 3) {
            throw new \RuntimeException($error);
        }

        $start = $this->getPeriodDate($childNodes, 0,  'competitions start');
        $end = $this->getPeriodDate($childNodes, 2, 'competitions end ');

        return [$start, $end];
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function getPeriodDate(DOMNodeList $nodes, int $index, string $type): string
    {
        if ($nodes->item($index) === null) {
            throw new \RuntimeException('Error getting '.$type);
        }

        $date = trim($nodes->item($index)->textContent);

        return $this->formatDate($date, $type);
    }

    private function formatDate(string $date, string $type): string
    {
        $error = sprintf('Unexpected %s date value: %s', $type, $date);

        if (!(bool) preg_match('/^[A-Z]{1}[a-z]{2}\s\d{1,2},\s\d{4}$/', $date)) {
            throw new \RuntimeException($error);
        }

        $tz = new \DateTimeZone('UTC');
        $date = \DateTimeImmutable::createFromFormat('M j, Y', $date, $tz);

        if (false === $date) {
            throw new \RuntimeException($error);
        }

        return $date->format('Y-m-d');
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function getEventValues(?DOMNode $contextNode): array
    {
        $nodes = $this->xpath->start()
            ->with('/a[@class="competition-link"]')
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

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function getNumericValue(DOMNodeList $nodes, int $index, string $type): string
    {
        if ($nodes->item($index) === null) {
            throw new \RuntimeException('Error getting '.$type);
        }

        $value = trim($nodes->item($index)->textContent);

        if (strlen($value) === 0 || !is_numeric($value)) {
            $value = '0.0';
        }

        return $value;
    }

    private function getUpdated(?DOMNode $node, string $type): string
    {
        if ($node === null) {
            throw new \RuntimeException('Error getting '.$type);
        }

        // updated values can be empty
        $value = trim($node->textContent);

        if (strlen($value) !== 0) {
            return $this->formatDate($value, 'competitions updated');
        }

        return $value;
    }
}
