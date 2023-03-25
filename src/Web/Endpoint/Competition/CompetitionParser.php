<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\DomUtils;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParserInterface;
use Wprs\Api\Web\Endpoint\XPathDom;

class CompetitionParser implements ParserInterface
{
    private XPathDom $xpath;

    public function parse(string $html, ?FilterInterface $filter = null): DataCollector
    {
        $this->xpath = new XPathDom($html);

        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        // Get comp name
        $compName = $this->getCompName($wrapper);

        $detailsTable = $this->xpath->getElementById('tableMain', $wrapper);
        $pilotTable = $this->xpath->getElementById('w1', $wrapper);

        // Check columns for both tables
        $this->checkColumnCount($detailsTable, 17);
        $this->checkColumnCount($pilotTable, 8);

        // details table
        $detailsRow = $this->getTablesRows($detailsTable, 1);
        $details = $this->parseDetailsRow($detailsRow->item(0), $compName);
        $dataCollector->addExtra('details', $details);

        // pilot table
        $rows = $this->getTablesRows($pilotTable, $overallCount);

        foreach ($rows as $pilotRow) {
            $item = $this->parsePilotRow($pilotRow);
            $dataCollector->add($item, $filter);
        }

        return $dataCollector;
    }

    private function getOverallCount(DOMNode $context): int
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="table-title-row"]/div')
            ->withClassContains('count-pilots')
            ->with('//span[@class="count"]')
            ->query($context);

        $value = DomUtils::getSingleNodeText($nodes, 'pilots count');

        return (int) $value;
    }

    private function getCompName(DOMNode $context): string
    {
        $nodes = $this->xpath->start()
            ->with('/div[@class="header-rankings"]/h2')
            ->query($context);

        return DomUtils::getSingleNodeText($nodes, 'competition name');
    }

    private function checkColumnCount(DOMNode $context, int $expected): void
    {
        $nodes = $this->xpath->start()
            ->with('//thead/tr/th')
            ->query(($context));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competition criteria table columns, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }
    }

    private function getTablesRows(DOMNode $context, int $expected): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//tbody/tr')
            ->query(($context));

        if ($nodes->length !== $expected) {
            $format = 'expected %d competitions rows, got %d';
            throw new \RuntimeException(sprintf($format, $expected, $nodes->length));
        }

        return $nodes;
    }

    /**
     * @return array<string, string|int>
     */
    private function parseDetailsRow(DOMNode $context, string $compName): array
    {
        $columns = $this->getColumns($context);
        list($start, $end) = $this->getPeriod($columns->item(0));

        $result = [
            'name' => $compName,
            'id' => 0,
            'start_date' => $start,
            'end_date' => $end,
        ];

        $key = 'ta';
        $result[$key] = $this->getNumericValue($columns, 2, $key);

        $key = 'pn';
        $result[$key] = $this->getNumericValue($columns, 3, $key);

        $key = 'pq';
        $result[$key] = $this->getNumericValue($columns, 4, $key);

        $key = 'td';
        $result[$key] = $this->getNumericValue($columns, 5, $key);

        $key = 'tasks';
        $result[$key] = (int) $this->getNumericValue($columns, 6, $key);

        $key = 'pq_srp';
        $result[$key] = $this->getNumericValue($columns, 7, $key);

        $key = 'pq_srtp';
        $result[$key] = $this->getNumericValue($columns, 8, $key);

        $key = 'pilots';
        $result[$key] = (int) $this->getNumericValue($columns, 9, $key);

        $key = 'pq_rank_date';
        $result[$key] = $this->getDateValue($columns->item(10), $key);

        $key = 'pilots_last_12-months';
        $result[$key] = (int) $this->getNumericValue($columns, 11, $key);

        $key = 'comps_last_12_months';
        $result[$key] = (int) $this->getNumericValue($columns, 12, $key);

        $key = 'days_since_end';
        $result[$key] = (int) $this->getNumericValue($columns, 13, $key);

        $key = 'last_score';
        $result[$key] = $this->getNumericValue($columns, 14, $key);

        $key = 'winner_score';
        $result[$key] = $this->getNumericValue($columns, 15, $key);

        $key = 'updated';
        $result[$key] = $this->getDateValue($columns->item(16), $key);

        return $result;
    }

    /**
     * @return non-empty-array<string, string|int>
     */
    private function parsePilotRow(DOMNode $contextNode): array
    {
        $columns = $this->getColumns($contextNode);
        $result = [];

        $key = 'rank';
        $result[$key] = (int) $this->getNumericValue($columns, 0, $key);

        $key = 'pp';
        $result[$key] = $this->getNumericValue($columns, 1, $key);

        $key = 'points';
        $result[$key] = $this->getNumericValue($columns, 2, $key);

        $key = 'td_points';
        $result[$key] = $this->getNumericValue($columns, 3, $key);

        $key = 'score';
        $result[$key] = $this->getNumericValue($columns, 4, $key);

        $key = 'pilot';
        $result[$key] = trim($columns->item(5)->nodeValue);

        $key = 'civil_id';
        $result[$key] = (int) $this->getNumericValue($columns, 7, $key);

        return $result;
    }

    private function getColumns(DOMNode $context): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('/td')
            ->query($context);

        return $nodes;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getPeriod(DOMNode $context): array
    {
        $childNodes = $context->childNodes;

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

    private function getNumericValue(DOMNodeList $nodes, int $index, string $type): string
    {
        $value = trim($nodes->item($index)->nodeValue);

        if (strlen($value) === 0 || !is_numeric($value)) {
            throw new \RuntimeException('Missing value for '.$type);
        }

        return $value;
    }

    private function getDateValue(DOMNode $node, string $type): string
    {
        // Pq Rank Date and Results Updated values can be empty
        $value = trim($node->nodeValue);

        if (strlen($value) !== 0) {
            return $this->formatDate($value, $type);
        }

        return $value;
    }
}
