<?php declare(strict_types=1);

namespace Wprs\Api\Web;

interface ParserInterface
{
    public function parse(string $html, ?FilterInterface $filter = null): DataCollector;
}
