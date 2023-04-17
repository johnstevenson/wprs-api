<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

/**
 * @phpstan-import-type apiItem from ApiOutput
 * @phpstan-import-type apiErrors from ApiOutput
 */
interface FilterInterface
{
    /**
     * @phpstan-param apiItem $item
     * @phpstan-param apiErrors $errors
     * @phpstan-return apiItem|null
     */
    public function filter(array $item, ?array &$errors): ?array;
}
