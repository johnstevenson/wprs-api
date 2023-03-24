<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMElement;
use \DOMNodeList;

class DomUtils
{
    public static function getElementText(DOMElement $element, string $type): string
    {
        $childNodes = $element->childNodes;

        if ($childNodes->length !== 1 || !($childNodes->item(0) instanceof \DOMText)) {
            throw new \RuntimeException('Error getting '.$type);
        }

        return trim($element->nodeValue);
    }

    public static function getSingleNodeText(DOMNodeList $nodes, string $type): string
    {
        if ($nodes->length !== 1) {
            throw new \RuntimeException('Error getting '.$type);
        }

        return self::getElementText($nodes->item(0), $type);
    }

    public static function split(string $delimeter, string $value, int $expected, string $type): array
    {
        $result = array_map('trim', explode($delimeter, $value));

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
