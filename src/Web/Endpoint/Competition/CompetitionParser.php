<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParserManager;
use Wprs\Api\Web\Endpoint\Utils;
use Wprs\Api\Web\Endpoint\XPathDom;

/**
 * @phpstan-import-type apiItem from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class CompetitionParser extends ParserManager
{
    protected function run(): DataCollector
    {
        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        if ($wrapper === null) {
            throw new \RuntimeException('id=rankingTableWrapper');
        }

        $updated = $this->getRankingUpdated($wrapper);

        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount, $updated);

        // Get comp name - throws if missing or empty
        $compName = $this->getCompName($wrapper);

        $detailsTable = $this->xpath->getElementById('tableMain', $wrapper);
        if ($detailsTable === null) {
            throw new \RuntimeException('id=tableMain for details table');
        }

        $pilotTable = $this->xpath->getElementById('w1', $wrapper);
        if ($pilotTable === null) {
            throw new \RuntimeException('id=w1 for pilots table');
        }

        // Check columns for both tables
        $this->checkColumnCount($detailsTable, 17, 'details table');
        $this->checkColumnCount($pilotTable, 8, 'pilots table');

        // details table row
        $detailsRows = $this->getTableRows($detailsTable, 1, 'details table');

        // keep phpstan happy
        if ($detailsRows->item(0) === null) {
            throw new \RuntimeException('details table row');
        }

        $rawDetails = $this->parseDetailsRow($detailsRows->item(0), $compName);
        list($details, $errors) = $this->formatDetails($rawDetails);
        $dataCollector->addExtra('details', $details, $errors);

        if ($overallCount === 0) {
            return $dataCollector;
        }

        // pilot table
        $rows = $this->getTableRows($pilotTable, $overallCount, 'pilots table');

        foreach ($rows as $index => $pilotRow) {
            $rawItem = $this->parsePilotRow($pilotRow);
            list($item, $errors) = $this->formatItem($rawItem);
            $dataCollector->addItem($item, $errors, $this->filter);
        }

        return $dataCollector;
    }

    private function getOverallCount(DOMNode $contextNode): int
    {
        $nodes = $this->xpath->start()
            ->with('//div[@class="table-title-row"]/div')
            ->withClassContains('count-pilots')
            ->with('//span[@class="count"]')
            ->query($contextNode);

        $value = Utils::getNumberFromNodeList($nodes);

        if ($value === null) {
            throw new \RuntimeException('pilots count');
        }

        return (int) $value;
    }

    private function getCompName(DOMNode $contextNode): string
    {
        $nodes = $this->xpath->start()
            ->with('/div[@class="header-rankings"]/h2')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);

        if ($value === null || Utils::isEmptyString($value)) {
            throw new \RuntimeException('competition name');
        }

        return $value;
    }

    private function checkColumnCount(DOMNode $contextNode, int $expected, string $name): void
    {
        $nodes = $this->xpath->start()
            ->with('//thead/tr/th')
            ->query(($contextNode));

        if ($nodes->length !== $expected) {
            $message = Utils::getCountMessage($expected, $name, 'columns', $nodes->length);
            throw new \RuntimeException($message);
        }
    }

    /**
     * @return DOMNodeList<DOMNode>
     */
    private function getTableRows(DOMNode $contextNode, int $expected, string $name): DOMNodeList
    {
        $nodes = $this->xpath->start()
            ->with('//tbody/tr')
            ->query(($contextNode));

        if ($nodes->length !== $expected) {
            $message = Utils::getCountMessage($expected, $name, 'rows', $nodes->length);
            throw new \RuntimeException($message);
        }

        return $nodes;
    }

    /**
     * @return non-empty-array<string, string>
     */
    private function parseDetailsRow(DOMNode $contextNode, string $compName): array
    {
        $columns = $this->getColumns($contextNode);
        list($start, $end) = $this->getCompPeriod($columns->item(0));

        $result = [
            'name' => $compName,
            'id' => '0',
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
        $result[$key] = $this->getNumericValue($columns, 6, $key);

        $key = 'pq_srp';
        $result[$key] = $this->getNumericValue($columns, 7, $key);

        $key = 'pq_srtp';
        $result[$key] = $this->getNumericValue($columns, 8, $key);

        $key = 'pilots';
        $result[$key] = $this->getNumericValue($columns, 9, $key);

        $key = 'pq_rank_date';
        $result[$key] = $this->getDateValue($columns->item(10), $key);

        $key = 'pilots_last_12_months';
        $result[$key] = $this->getNumericValue($columns, 11, $key);

        $key = 'comps_last_12_months';
        $result[$key] = $this->getNumericValue($columns, 12, $key);

        $key = 'days_since_end';
        $result[$key] = $this->getNumericValue($columns, 13, $key);

        $key = 'last_score';
        $result[$key] = $this->getNumericValue($columns, 14, $key);

        $key = 'winner_score';
        $result[$key] = $this->getNumericValue($columns, 15, $key);

        $key = 'updated';
        $result[$key] = $this->getDateValue($columns->item(16), $key);

        return $result;
    }

    /**
     * @param non-empty-array<string, string> $details
     * @return array{0: non-empty-array<string, string|int>, 1: array<string>|null}
     */
    private function formatDetails(array $details): array
    {
        $result = $details;
        $result['id'] = 0;
        $errors = [];

        foreach ($this->getMissingDetailsTypes() as $key => $type) {
            $value = $details[$key];
            $isInteger = $type === 'int';

            if (Utils::isEmptyString($value)) {
                $errors[] = Utils::makeDetailsError($key);
                $result[$key] = $isInteger ? '0' : '0.0';
            }

            if ($isInteger) {
                $result[$key] = (int) $result[$key];
            }
        }

        return [$result, count($errors) !== 0 ? $errors : null];
    }


    /**
     * @return non-empty-array<string, string>
     */
    private function parsePilotRow(DOMNode $contextNode): array
    {
        $columns = $this->getColumns($contextNode);
        $result = [];

        $key = 'rank';
        $result[$key] = $this->getNumericValue($columns, 0, $key);

        $key = 'pp';
        $result[$key] = $this->getNumericValue($columns, 1, $key);

        $key = 'points';
        $result[$key] = $this->getNumericValue($columns, 2, $key);

        $key = 'td_points';
        $result[$key] = $this->getNumericValue($columns, 3, $key);

        $key = 'score';
        $result[$key] = $this->getNumericValue($columns, 4, $key);

        $key = 'pilot';
        $result[$key] = $this->getLinkValue($columns->item(5), false, $key);

        $key = 'nation_cc';
        $result[$key] = $this->getTextValue($columns, 6, $key);

        $key = 'civl_id';
        $result[$key] = $this->getLinkValue($columns->item(7), true, $key);

        return $result;
    }

    /**
     * @param non-empty-array<string, string> $item
     * @return array{0: apiItem, 1: array<string>|null}
     */
    private function formatItem(array $item): array
    {
        $result = $item;
        $errors = [];

        foreach ($this->getMissingItemTypes() as $key => $type) {
            $value = $item[$key];
            $isInteger = $type === 'int';

            if (Utils::isEmptyString($value)) {
                $errors[] = $key;
                $isFloat = $type === 'float';
                $result[$key] = $isInteger ? '0' : ($isFloat ? '0.0' : '');
            }

            if ($isInteger) {
                $result[$key] = (int) $result[$key];
            }
        }

        return [$result, count($errors) !== 0 ? $errors : null];
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
    private function getCompPeriod(?DOMNode $contextNode): array
    {
        $error = 'competition period';

        if ($contextNode === null) {
            throw new \RuntimeException($error);
        }

        $childNodes = $contextNode->childNodes;

        // expecting start <br/> end
        if ($childNodes->length !== 3) {
            throw new \RuntimeException($error);
        }

        $format = 'M j, Y';

        $start = Utils::getDateFromNodeList($childNodes, 0, $format );
        if ($start === null) {
            throw new \RuntimeException('competition start date');
        }

        $end = Utils::getDateFromNodeList($childNodes, 2, $format );
        if ($end === null) {
            throw new \RuntimeException('competition end date');
        }

        return [$start, $end];
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function getNumericValue(DOMNodeList $nodes, int $index, string $name): string
    {
        $value = Utils::getNumberFromNodeListLax($nodes, $index);
        if ($value === null) {
            throw new \RuntimeException('pilot '.$name);
        }

        return $value;
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function getTextValue(DOMNodeList $nodes, int $index, string $name): string
    {
        $value = Utils::getTextFromNodeList($nodes, $index);
        if ($value === null) {
            throw new \RuntimeException('pilot '.$name);
        }

        return $value;
    }

    private function getDateValue(?DOMNode $node, string $name): string
    {
        $value = Utils::getNodeText($node);
        if ($value === null) {
            throw new \RuntimeException($name);
        }

        // Pq Rank Date and Results Updated values can be empty
        if (Utils::isEmptyString($value)) {
            return $value;
        }

        $value = Utils::formatDate($value, 'M j, Y');
        if ($value === null) {
            throw new \RuntimeException($name);
        }

        return $value;
    }

    private function getLinkValue(?DOMNode $contextNode, bool $isNumeric, string $name): string
    {
        $nodes = $this->xpath->start()
            ->with('/a')
            ->query($contextNode);

        if ($isNumeric) {
             $value = Utils::getNumberFromNodeList($nodes);
        } else {
             $value = Utils::getTextFromNodeList($nodes);
        }

        if ($value === null) {
            throw new \RuntimeException($name);
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private function getMissingDetailsTypes(): array
    {
        // we ignore pq_rank_date and updated dates as these are already missing

        $float = 'float';
        $int = 'int';

        return [
            'ta' => $float,
            'pn' => $float,
            'pq' => $float,
            'td' => $float,
            'pq_srp' => $float,
            'pq_srtp' => $float,
            'tasks' => $int,
            'pilots' => $int,
            'pilots_last_12_months' => $int,
            'comps_last_12_months' => $int,
            'days_since_end' => $int,
            'last_score' => $float,
            'winner_score' => $float,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getMissingItemTypes(): array
    {
        $float = 'float';
        $int = 'int';
        $string = 'string';

        return [
            'rank' => $int,
            'pp' => $float,
            'points' => $float,
            'td_points' => $float,
            'score' => $int,
            'pilot' => $string,
            'nation_cc' => $string,
            'civl_id' => $int,
        ];
    }
}
