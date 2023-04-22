<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMNode;
use \DOMNodeList;

use Wprs\Api\Web\System;
use Wprs\Api\Web\WprsException;

abstract class ParserManager implements ParserInterface
{
    protected ?FilterInterface $filter;
    protected XPathDom $xpath;

    abstract protected function run(): DataCollector;

    public function parse(string $html, ?FilterInterface $filter = null): DataCollector
    {
        $this->xpath = new XPathDom($html);
        $this->filter = $filter;

        try {
            $dataCollector = $this->run();
        } catch (\RuntimeException $e) {
            $message = System::formatMessage(static::class, 'error getting');
            throw new WprsException($message, $e);
        }

        return $dataCollector;
    }

    protected function getRankingUpdated(DOMNode $contextNode): string
    {
        $value = $this->getUpdated($contextNode);
        if ($value === null) {
            throw new \RuntimeException('ranking updated');
        }

        return $value;
    }

    private function getUpdated(DOMNode $contextNode): ?string
    {
        $nodes = $this->xpath->start()
            ->with('//div')
            ->withClassContains('header-rankings')
            ->with('//div')
            ->withClassContains('text-muted')
            ->query($contextNode);

        $value = Utils::getTextFromNodeList($nodes);

        // expecting: Ranking updated: Apr 1, 2023 03:05
        if ($value === null) {
            return null;
        }

        $parts = Utils::split(':', $value, -1);
        if ($parts === null) {
            return null;
        }

        $matched = (bool) preg_match('/Ranking\\s+updated/i', $parts[0]);
        if (!$matched) {
            return null;
        }

        $date = $parts[1].':'.$parts[2];

        return Utils::formatDateTime($date, 'M j, Y h:i');
    }
}
