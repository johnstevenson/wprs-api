<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Web\Endpoint\ParamsInterface;

class CompetitionsParams implements ParamsInterface
{
    /**
     * @phpstan-return non-empty-array<string, string>
     */
    public function getQueryParams(string $rankingDate): array
    {
        return ['rankingDate' => $rankingDate];
    }

    /**
     * @phpstan-return array{}
     */
    public function getDetails(): array
    {
        return [];
    }
}
