<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;

use Wprs\Api\Web\Endpoint\Utils;
use Wprs\Api\Web\Endpoint\XPathDom;

class HtmlFormatter
{
    /** @var array<DOMNode> */
    private array $removals = [];

    public function format(string $html): string
    {
        $xpath = new XPathDom($html);

        $nodes = $xpath->start()->with('//text()')->query();
        $this->removeWhitespace($nodes);

        $this->removeHeadElements($xpath);
        $this->removeElements($xpath, 'header');
        $this->removeElements($xpath, 'form');
        $this->removeElements($xpath, 'footer');
        $this->removeElements($xpath, 'ul');
        $this->removeElements($xpath, 'script');
        $this->removeElements($xpath, 'comment()');
        $this->removeExportWidget($xpath);

        return $this->prettify($xpath);
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function removeWhitespace(DOMNodeList $nodes): void
    {
        /** @var DOMNode $node */
        foreach($nodes as $node) {
            $node->nodeValue = (string) $node->nodeValue;

            $node->nodeValue = ltrim($node->nodeValue);
            $node->nodeValue = rtrim($node->nodeValue);

            while (strpos($node->nodeValue, '  ') !== false) {
                $node->nodeValue = str_replace('  ', ' ', $node->nodeValue);
            }

            if (Utils::isEmptyString($node->nodeValue)) {
                $this->removals[] = $node;
            }
        }

        $this->removeNodes();
    }

    private function removeHeadElements(XPathDom $xpath): void
    {
        $nodes = $xpath->start()->with('./head')->query();
        $head = $nodes->item(0);

        if ($head === null) {
            return;
        }

        $this->removeChildren($head);
        $meta = $xpath->getDom()->createElement('meta');
        $node = $head->appendChild($meta);

        if ($node instanceof DOMElement) {
            $node->setAttribute('charset', 'UTF-8');
        }
    }

    private function removeExportWidget(XPathDom $xpath): void
    {
        // this has a session-generated id which changes on each build
        $nodes = $xpath->start()
            ->with('//div')
            ->withClassContains('jsExportWidget')
            ->with('/parent::*')
            ->query();

        $div = $nodes->item(0);

        if ($div === null) {
            return;
        }

        $this->removeChildren($div);

        // only remove the parent if it is empty
        if ($div->nodeValue !== null && Utils::isEmptyString($div->nodeValue)) {
            $this->removals[] = $div;
            $this->removeNodes();
        }
    }


    private function removeElements(XPathDom $xpath, string $type): void
    {
        $nodes = $xpath->start()->with('//'.$type)->query();
        $this->removeItems($nodes);
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    private function removeItems(DOMNodeList $nodes): void
    {
        foreach ($nodes as $node) {
            $this->removals[] = $node;
        }

        $this->removeNodes();
    }

    private function removeChildren(DOMNode $parent): void
    {
        foreach ($parent->childNodes as $node) {
            $this->removals[] = $node;
        }

        $this->removeNodes();
    }

    private function removeNodes(): void
    {
        foreach ($this->removals as $node) {
            if ($node->parentNode !== null) {
                $node->parentNode->removeChild($node);
            }
        }

        $this->removals = [];
    }

    private function prettify(XPathDom $xpath): string
    {
        $xpath->getDom()->formatOutput = true;
        $html = (string) $xpath->getDom()->saveXML();

        $index = stripos($html, '<html');

        if ($index !== false) {
            $html = substr($html, $index);
        }

        return "<!DOCTYPE html>\n".$html;
    }
}
