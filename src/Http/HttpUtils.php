<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class HttpUtils
{
    /**
     * @param non-empty-array<string, string> $params
     */
    public static function buildQuery(array $params, string $path): string
    {
        $pairs = [];

        foreach ($params as $name => $value) {
            $name = self::queryStringEncode($name);
            $value = self::queryStringEncode($value);
            $pairs[] = $name.'='.$value;
        }

        $query = implode('&', $pairs);

        return $path.'?'.$query;
    }

    public static function getResponseContent(Response $response): string
    {
        $result = $response->contents;

        // Check for gzip magic number
        if ("\x1f\x8b" === substr($result, 0, 2)) {
            $result = (string) gzdecode($result);
        }

        return $result;
    }

    /**
     * Encodes a query string as per whatwg.org Url standard
     *
     * https://url.spec.whatwg.org/
     *
     * @param string $value
     * @return string
     */
    public static  function queryStringEncode(string $value): string
    {
        $result = '';

        // query percent-encode set: x22 (") 34, x23 (#) 35, x3C (<) 60, x3E (>) 62
        $encodeSet = [34, 35, 60, 62];

        for ($i = 0, $len = strlen($value); $i < $len; ++$i) {
            $c = $value[$i];
            $dec = ord($c);

            // C0 control or space, upper ascii bound x7E (~) 126
            if ($dec < 33 || $dec > 126 || in_array($dec, $encodeSet, true)) {
                $c = '%'.bin2hex($c);
            }

            $result .= $c;
        }

        return $result;
    }
}
