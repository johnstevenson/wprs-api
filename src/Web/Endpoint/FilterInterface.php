<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

interface FilterInterface
{
    public function filter(array $item): ?array;

    public function setOptions(array $options): void;
}
