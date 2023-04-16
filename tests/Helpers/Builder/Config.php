<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use \stdClass;

use Wprs\Api\Web\System;

class Config
{
    private int $discipline;
    private string $activity;
    private int $regionId;
    private int $compId;
    private string $rankingDate;

    public function __construct(?stdClass $data = null)
    {
        if ($data !== null) {
            $this->discipline = $data->discipline;
            $this->activity = $data->activity;
            $this->rankingDate = $data->rankingDate;
            $this->regionId = $data->regionId;
            $this->compId = $data->compId;

        } else {
            $this->discipline = System::DISCIPLINE_PG_XC;
            $this->activity = System::getDisciplineForDisplay($this->discipline);
            $this->rankingDate = System::getRankingDate(null);
            $this->regionId = System::REGION_EUROPE;
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

    public function getRankingDate(): string
    {
        return $this->rankingDate;
    }

    public function getCompId(): int
    {
        return $this->compId;
    }

    public function setCompId(int $id): void
    {
        $this->compId = $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(int $compId): array
    {
        $this->compId = $compId;

        return get_object_vars($this);
    }
}
