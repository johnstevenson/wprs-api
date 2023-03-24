<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMDocument;
use \DOMElement;
use \DOMNode;
use \DOMNodeList;
use \DOMXPath;

class XPathDom
{
    private array $parts = [];
    private DOMDocument $dom;
    private DomXPath $xpath;

    public function __construct(string $html)
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;

        $this->load($html);
        $this->xpath = new DomXPath($this->dom);
    }

    public function getDom(): DOMDocument
    {
        return $this->dom;
    }

    public function getElementById(string $id, ?DOMNode $context = null, bool $throwOnError = true): ?DOMNode
    {
        $expression = sprintf('//*[@id="%s"]', $id);
        $this->parts = [$expression];
        $nodeList = $this->query($context);

        if ($nodeList->length !== 1) {
            if ($throwOnError) {
                throw new \RuntimeException('Element not found from id='.$id);
            }
            return null;
        }

        return $nodeList->item(0);
    }

    public function start(): self
    {
        $this->parts = [];

        return $this;
    }

    public function with(string $tag): self
    {
        $this->parts[] = $tag;

        return $this;
    }

    public function withClassContains(string $className): self
    {
        $contains = $this->getClassContains($className);
        $this->parts[] = sprintf('[%s]', $contains);

        return $this;
    }

    public function withClassContainsList(array $classNames): self
    {
        $contains = [];

        foreach ($classNames as $className) {
            $contains[] = $this->getClassContains($className);
        }

        $this->parts[] = sprintf('[%s]', implode(' and ', $contains));

        return $this;
    }

    public function query(?DOMNode $context = null): DOMNodeList
    {
        $expression = implode('', $this->parts);

        if (strlen($expression) === 0) {
            throw new \RuntimeException('No expression set for xpath');
        }

        // Add initial root element if we have context
        if ($context !== null && $expression[0] !==  '.') {
            $expression = '.'.$expression;
        }

        $result = $this->xpath->query($expression, $context);

        if (false === $result) {
            throw new \RuntimeException('Invalid xpath expression: '.$expression);
        }

        return $result;
    }

    private function getClassContains(string $className)
    {
        $concat = 'concat(" ", normalize-space(@class), " ")';
        return sprintf('contains(%s, " %s ")', $concat, $className);
    }

    private function load($html)
    {
        libxml_use_internal_errors(true);

        $result = $this->dom->loadHTML('<?xml version="1.0" encoding="UTF-8">'.trim($html));
        libxml_clear_errors();

        if (!$result) {
            throw new \RuntimeException('Unable to load DOM html');
        }
    }
}
