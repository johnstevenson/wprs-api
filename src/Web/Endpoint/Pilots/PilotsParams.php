<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use Wprs\Api\Web\ParamsInterface;
use Wprs\Api\Web\Rank;

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

    public function getQueryParams(string $rankingDate): array
    {
        $params = [
            'search[rankingDate]' => $rankingDate,
            'search[continent_id]' => (string) $this->regionId,
        ];

        if (null !== $this->nationId) {
            $params['search[nation_id]'] = (string) $this->nationId;
        }

        if (null !== $this->scoring && $this->scoring !== Rank::SCORING_OVERALL) {
            $params['search[scoringCategory]'] = (string) Rank::getScoring($this->scoring) ;
        }

        return $params;
    }

    public function getDetails(): array
    {
        $meta = [
            'region' => Rank::getRegion($this->regionId),
            'region_id' => $this->regionId,
        ];

        if (null !== $this->nationId) {
            $meta['nation'] = '';
            $meta['nation_id'] = $this->nationId;
        }

        $scoring = $this->scoring ?? Rank::SCORING_OVERALL;
        $meta['scoring'] = Rank::getScoring($scoring);

        return $meta;
    }

    private function setRegionId(int $regionId)
    {
        Rank::getRegion($regionId);
        $this->regionId = $regionId;
    }

    private function setScoring(?int $scoring)
    {
        if (null !== $scoring) {
            Rank::getScoring($scoring);
        }

        $this->scoring = $scoring;
    }
}
