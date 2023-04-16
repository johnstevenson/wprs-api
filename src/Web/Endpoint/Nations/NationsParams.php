<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Nations;

use Wprs\Api\Web\Endpoint\ParamsInterface;
use Wprs\Api\Web\System;

class NationsParams implements ParamsInterface
{
    public int $regionId;

    public function __construct(?int $regionId)
    {
        $this->setRegionId($regionId);
    }

    /**
     * @phpstan-return non-empty-array<string, string>
     */
    public function getQueryParams(string $rankingDate): array
    {
        return [
            'search[rankingDate]' => $rankingDate,
            'RankingNationalTotalSearch[continent_id]' => (string) $this->regionId,
        ];
    }

    /**
     * @phpstan-return non-empty-array<string, string|int>
     */
    public function getDetails(): array
    {
        return [
            'region' => System::getRegion($this->regionId),
            'region_id' => $this->regionId,
        ];
    }

    private function setRegionId(?int $regionId): void
    {
        if ($regionId === null) {
            $this->regionId = System::REGION_WORLD;
            return;
        }

        // check region
        System::getRegion($regionId);
        $this->regionId = $regionId;
    }
}
