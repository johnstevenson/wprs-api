<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

/**
 * @phpstan-import-type apiItem from ApiOutput
 */
interface FilterInterface
{
    /**
     * @phpstan-param apiItem $item
     * @phpstan-return apiItem|null
     */
    public function filter(array $item): ?array;

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): void;
}
