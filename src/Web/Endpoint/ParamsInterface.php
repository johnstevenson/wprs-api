<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

interface ParamsInterface
{
    /**
     * @phpstan-return non-empty-array<string, string>
     */
    public function getQueryParams(string $rankingDate): array;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getDetails(): array;
}
