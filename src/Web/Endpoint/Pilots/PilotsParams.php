<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use Wprs\Api\Web\Endpoint\ParamsInterface;
use Wprs\Api\Web\System;

class PilotsParams implements ParamsInterface
{
    public int $regionId;
    public ?int $nationId;
    public ?int $scoring;

    public function __construct(int $regionId, ?int $nationId = null, ?int $scoring = null)
    {
        $this->setRegionId($regionId);
        $this->nationId = $nationId;
        $this->setScoring($scoring);
    }

    /**
     * @phpstan-return non-empty-array<string, string>
     */
    public function getQueryParams(string $rankingDate): array
    {
        $params = [
            'search[rankingDate]' => $rankingDate,
            'search[continent_id]' => (string) $this->regionId,
        ];

        if (null !== $this->nationId) {
            $params['search[nation_id]'] = (string) $this->nationId;
        }

        if (null !== $this->scoring && $this->scoring !== System::SCORING_OVERALL) {
            $params['search[scoringCategory]'] = System::getScoring($this->scoring) ;
        }

        return $params;
    }

    /**
     * @phpstan-return non-empty-array<string, string|int>
     */
    public function getDetails(): array
    {
        $meta = [
            'region' => System::getRegion($this->regionId),
            'region_id' => $this->regionId,
        ];

        if (null !== $this->nationId) {
            $meta['nation'] = '';
            $meta['nation_id'] = $this->nationId;
        }

        $scoring = $this->scoring ?? System::SCORING_OVERALL;
        $meta['scoring'] = System::getScoring($scoring);

        return $meta;
    }

    private function setRegionId(int $regionId): void
    {
        System::getRegion($regionId);
        $this->regionId = $regionId;
    }

    private function setScoring(?int $scoring): void
    {
        if (null !== $scoring) {
            System::getScoring($scoring);
        }

        $this->scoring = $scoring;
    }
}
