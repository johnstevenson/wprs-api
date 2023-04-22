<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use \stdClass;

use Wprs\Api\Web\System;

class Config
{
    private int $discipline;
    private string $activity;
    private string $rankingDate;
    private string $updated;
    private int $regionId;
    private ?int $nationId;
    private int $pilotsCount;
    private int $pilotsMax;
    private int $compId;

    public function __construct(?stdClass $data = null)
    {
        if ($data !== null) {
            $this->discipline = $data->discipline;
            $this->activity = $data->activity;
            $this->rankingDate = $data->rankingDate;
            $this->updated = $data->updated;
            $this->regionId = $data->regionId;
            $this->nationId = $data->nationId;
            $this->pilotsCount = $data->pilotsCount;
            $this->pilotsMax = $data->pilotsMax;
            $this->compId = $data->compId;
        } else {
            $this->discipline = System::DISCIPLINE_PG_XC;
            $this->activity = System::getDisciplineForDisplay($this->discipline);
            $this->rankingDate = System::getRankingDate(null);
            $this->updated = '';
            $this->regionId = System::REGION_EUROPE;
            $this->nationId = 233;
            $this->pilotsCount = 0;
            $this->pilotsMax = 0;
            $this->compId = 0;
        }
    }

    public function getActivity(): string
    {
        return $this->activity;
    }

    public function getDiscipline(): int
    {
        return $this->discipline;
    }

    public function getRegionId(): int
    {
        return $this->regionId;
    }

    public function getNationId(): ?int
    {
        return $this->nationId;
    }

    public function getRankingDate(): string
    {
        return $this->rankingDate;
    }

    public function getCompId(): int
    {
        return $this->compId;
    }

    public function getPilotsCount(): int
    {
        return $this->pilotsCount;
    }

    public function getPilotsMax(): int
    {
        return $this->pilotsMax;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(string $updated, int $pilotsCount, int $pilotsMax, int $compId): array
    {
        $this->updated = $updated;
        $this->pilotsCount = $pilotsCount;
        $this->pilotsMax = $pilotsMax;
        $this->compId = $compId;

        return get_object_vars($this);
    }
}
