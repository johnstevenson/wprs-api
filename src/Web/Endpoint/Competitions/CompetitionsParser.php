<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

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
class CompetitionsParser extends ParserManager
{
    protected function run(): DataCollector
    {
        // Containing wrapper div
        $wrapper = $this->xpath->getElementById('rankingTableWrapper');
        if ($wrapper === null) {
            throw new \RuntimeException('id=rankingTableWrapper.');
        }

        $overallCount = $this->getOverallCount($wrapper);
        $dataCollector = new DataCollector($overallCount);

        if (0 === $overallCount) {
            return $dataCollector;
        }

        $table = $this->xpath->getElementById('tableMain', $wrapper);
        if ($table === null) {
            throw new \RuntimeException('id=tableMain.');
        }

        $this->checkColumnCount($table, 15, 'main table');
        $rows = $this->getTableRows($table, $overallCount, 'main table');

        foreach ($rows as $index => $row) {
            $rawItem = $this->parseRow($row);
            $item = $this->formatItem($rawItem, $index, $dataCollector);
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
            ->with('//tbody/tr[@data-key]')
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
    private function parseRow(DOMNode $contextNode): array
    {
        $columns = $this->getColumns($contextNode);
        list($start, $end) = $this->getCompPeriod($columns->item(0));
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
        $result[$key] = $this->getNumericValue($columns, 7, $key);

        $key = 'pilots';
        $result[$key] = $this->getNumericValue($columns, 8, $key);

        $key = 'pilots_last_12_months';
        $result[$key] = $this->getNumericValue($columns, 9, $key);

        $key = 'comps_last_12_months';
        $result[$key] = $this->getNumericValue($columns, 10, $key);

        $key = 'days_since_end';
        $result[$key] = $this->getNumericValue($columns, 11, $key);

        $key = 'last_score';
        $result[$key] = $this->getNumericValue($columns, 12, $key);

        $key = 'winner_score';
        $result[$key] = $this->getNumericValue($columns, 13, $key);

        $key = 'updated';
        $result[$key] = $this->getDateValue($columns->item(14), $key);

        return $result;
    }

    /**
     * @param array<string, string> $item
     * @phpstan-return apiItem
     */
    private function formatItem(array $item, int $index, DataCollector $dataCollector): array
    {
        // we ignore updated date as this value is missing on some pre 2022 comps
        $result = $item;
        $result['id'] = (int) $item['id'];

        $hasResults = !(Utils::isEmptyString($item['tasks']) || Utils::isEmptyString('pilots'));

        $numerics = ['ta', 'pn', 'pq', 'td', 'last_score', 'winner_score'];

        foreach ($numerics as $key) {
            $value = $item[$key];

            if (Utils::isEmptyString($value)) {
                $error = Utils::makeItemError($key, $index);

                if ($hasResults) {
                    $dataCollector->addError($error);
                }
                $result[$key] = '0.0';
            }
        }

        $ints = ['tasks', 'pilots', 'pilots_last_12_months', 'comps_last_12_months', 'days_since_end'];

        foreach ($ints as $key) {
            $value = $item[$key];

            if (Utils::isEmptyString($value)) {
                $error = Utils::makeItemError($key, $index);

                if ($hasResults) {
                    $dataCollector->addError($error);
                }
                $result[$key] = '0';
            }

            $result[$key] = (int) $value;
        }

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
    private function getCompPeriod(?DOMNode $contextNode): array
    {
        $error = 'period';

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
            throw new \RuntimeException('start date');
        }

        $end = Utils::getDateFromNodeList($childNodes, 2, $format );
        if ($end === null) {
            throw new \RuntimeException('end date');
        }

        return [$start, $end];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function getEventValues(?DOMNode $contextNode): array
    {
        $nodes = $this->xpath->start()
            ->with('/a[@class="competition-link"]')
            ->query($contextNode);


        $name = Utils::getTextFromNodeList($nodes);
        if ($name === null) {
            throw new \RuntimeException('name');
        }

        $params = Utils::getLinkQueryParams($nodes->item(0));
        if ($params === null) {
            throw new \RuntimeException('href');
        }

        $id = $params['id'] ?? null;
        if (!is_string($id)) {
            throw new \RuntimeException('id');
        }

        return [$name, $id];
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function getNumericValue(DOMNodeList $nodes, int $index, string $name): string
    {
        // these values are '(not </br> set)' for comps without results
        $allowNames = ['last_score', 'winner_score'];
        $emptyString = '';

        $value = Utils::getNumberFromNodeListLax($nodes, $index);

        if ($value === null) {
            if (!in_array($name, $allowNames, true)) {
                throw new \RuntimeException($name);
            }
            $value = $emptyString;
        }

        // in case the '(not </br> set)' notation changes
        if (!Utils::isEmptyString($value) && !Utils::isNumericText($value)) {
            if (!in_array($name, $allowNames, true)) {
                throw new \RuntimeException($name);
            }
            $value = $emptyString;
        }

        return $value;
    }

    private function getDateValue(?DOMNode $node, string $name): string
    {
        $value = Utils::getNodeText($node);
        if ($value === null) {
            throw new \RuntimeException($name);
        }

        // Updated value can be empty
        if (Utils::isEmptyString($value)) {
            return $value;
        }

        $value = Utils::formatDate($value, 'M j, Y');
        if ($value === null) {
            throw new \RuntimeException($name);
        }

        return $value;
    }
}
