<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Exception;
use RuntimeException;

class WprsException extends RuntimeException
{
    public function __construct(string $message, ?Exception $e = null)
    {
        if ($e instanceof WprsException) {
            throw $e;
        }

        if ($e !== null) {
            $message = rtrim($message, ' ');
            $message = sprintf('%s %s', $message, static::formatMessage($e));
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
