<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use \DOMElement;
use \DOMNode;
use \DOMNodeList;

class Utils
{

    public static function isEmptyString(?string $value): bool
    {
        return $value === null ? true : strlen($value) === 0;
    }

    public static function isNumericText(?string $value): bool
    {
        return is_numeric($value);
    }

    public static function pluralize(string $value, int $number): string
    {
        $value = rtrim($value, 's');

        return $number !== 1 ? $value.'s' : $value;
    }

    public static function getCountMessage(int $expected, string $name, string $type, int $got): string
    {
        $type = self::pluralize($type, $expected);

        return sprintf('%d %s %s, got %d', $expected, $name, $type, $got);
    }

    public static function formatDate(string $value, string $format): ?string
    {
        $tz = new \DateTimeZone('UTC');
        $date = \DateTimeImmutable::createFromFormat($format, $value, $tz);

        if ($date === false) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    public static function makeDetailsError(string $key): string
    {
        return sprintf('details/%s', $key);
    }

    public static function makeItemError(string $key, int $index): string
    {
        return sprintf('items/%d/%s', $index, $key);
    }

    /**
     * @phpstan-param non-empty-string $separator
     * @return array<int, string>|null
     */
    public static function split(string $separator, string $value, int $expected): ?array
    {
        $result = array_map('trim', explode($separator, $value));

        if (count($result) !== $expected) {
            $result = null;
        }

        return $result;
    }

    public static function getNodeText(?DOMNode $node): ?string
    {
        if ($node === null) {
            return null;
        }

        $childNodes = $node->childNodes;

        if ($childNodes->length === 0) {
            return trim($node->textContent);
        }

        if ($childNodes->length === 1 && ($childNodes->item(0) instanceof \DOMText)) {
            return trim($node->textContent);
        }

        return null;
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getTextFromNodeList(DOMNodeList $nodes, int $index = 0): ?string
    {
        return self::getNodeText($nodes->item($index));
    }

    /**
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getDateFromNodeList(DOMNodeList $nodes, int $index, string $format): ?string
    {
        $value = self::getNodeText($nodes->item($index));

        return $value !== null ? self::formatDate($value, $format) : null;
    }

    /**
     * Returns null if a number is not found
     *
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getNumberFromNodeList(DOMNodeList $nodes, int $index = 0): ?string
    {
        $value = self::getNodeText($nodes->item($index));

        return self::isNumericText($value) ? $value : null;
    }

    /**
     * Returns null if the node or number is not found. Allows empty values
     *
     * @param DOMNodeList<DOMNode> $nodes
     */
    public static function getNumberFromNodeListLax(DOMNodeList $nodes, int $index = 0): ?string
    {
        $value = self::getNodeText($nodes->item($index));

        // allow empty values
        if ($value === null || self::isEmptyString($value)) {
            return $value;
        }

        return self::isNumericText($value) ? $value : null;
    }

    public static function getAttribute(?DOMNode $node, string $attribute): ?string
    {
        if (!($node instanceof DOMElement)) {
            return null;
        }

        return trim($node->getAttribute($attribute));
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public static function getLinkQueryParams(?DOMNode $node): ?array
    {
        $href = self::getAttribute($node, 'href');
        if ($href === null) {
            return null;
        }

        $query = parse_url($href, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $result);
        }

        return $result ?? null;
    }
}
