<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Exception;
use RuntimeException;

class WprsException extends RuntimeException
{
    public function __construct(string $message, ?Exception $e = null)
    {
        if ($e !== null) {
            $message = rtrim($message, chr(32));

            if ($e instanceof WprsException) {
                $extra = ltrim($e->getMessage(), chr(32));
            } else {
                $extra = static::formatMessage($e);
            }

            $message = sprintf('%s %s', $message, $extra);
        }

        parent::__construct($message, 0, null);
    }

    public static function formatMessage(Exception $e): string
    {
        return sprintf(
            "%s\nFile: %s\nLine: %d\n",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
    }
}
