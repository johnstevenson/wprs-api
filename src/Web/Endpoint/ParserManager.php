<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMNode;
use \DOMNodeList;
use Wprs\Api\Web\Rank;
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
            $message = Rank::formatMessage(static::class, 'error getting');
            throw new WprsException($message, $e);
        }

        return $dataCollector;
    }
}
