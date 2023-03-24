<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Web\Endpoint\DataCollector;

interface ParserInterface
{
    public function parse(string $html, ?FilterInterface $filter = null): DataCollector;
}
