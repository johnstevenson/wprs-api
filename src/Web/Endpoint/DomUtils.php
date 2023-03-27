<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;

class DomUtils
{
    public static function getElementText(DOMNode $node, string $type): string
    {
        $childNodes = $node->childNodes;

        if ($childNodes->length !== 1 || !($childNodes->item(0) instanceof \DOMText)) {
            throw new \RuntimeException('Error getting '.$type);
        }

        return trim($node->textContent);
    }

    public static function getAttribute(?DOMNode $node, string $attr, string $type): string
    {
        $error = sprintf('Error getting %s attribute for %s.', $attr, $type);

        if ($node === null) {
            throw new \RuntimeException($error);
        }
        // for phpstan
        if ($node instanceof DOMElement) {
            return trim($node->getAttribute($attr));
        }

        throw new \RuntimeException($error);
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getSingleNodeText(DOMNodeList $nodes, string $type): string
    {
        if ($nodes->length === 1 && $nodes->item(0) !== null) {
            return self::getElementText($nodes->item(0), $type);
        }

        throw new \RuntimeException('Error getting '.$type);
    }

    public static function getElementText2(DOMNode $node, string $type): ?string
    {
        $childNodes = $node->childNodes;

        if ($childNodes->length !== 1 || !($childNodes->item(0) instanceof \DOMText)) {
            return null;
        }

        return trim($node->textContent);
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getMultiNodeIndexText(DOMNodeList $nodes, int $index, string $type): ?string
    {
        if ($nodes->item($index) !== null) {
            return self::getElementText2($nodes->item($index), $type);
        }

        return null;
    }

    /**
     * @phpstan-param non-empty-string $separator
     * @return array<int, string>
     */
    public static function split(string $separator, string $value, int $expected, string $type): array
    {
        $result = array_map('trim', explode($separator, $value));

        if (count($result) !== $expected) {
            $msg = sprintf(
                'Error getting %s: expected %d values from split, got %d',
                $type,
                $expected,
                count($result)
            );
            throw new \RuntimeException($msg);
        }

        return $result;
    }
}
