<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Web\ParamsInterface;

class CompetitionsParams implements ParamsInterface
{
    public function getQueryParams(string $rankingDate): array
    {
        return ['rankingDate' => $rankingDate];
    }

    public function getMeta(): array
    {
        return [];
    }
}
