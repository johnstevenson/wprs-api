<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use Wprs\Api\Web\System;

class BuildConfig
{
    private int $discipline;
    private int $regionId;
    private int $compId;
    private string $rankingDate;

    public function __construct(?string $json = null)
    {
        if ($json !== null) {
            $config = json_decode($json);

            if (!($config instanceof \stdClass)) {
                 throw new \RuntimeException(json_last_error_msg());
            }

            $this->discipline = $config->discipline;
            $this->rankingDate = $config->rankingDate;
            $this->regionId = $config->regionId;
            $this->compId = $config->compId;

        } else {
            $this->discipline = System::DISCIPLINE_PG_XC;
            $this->rankingDate = System::getRankingDate(null);
            $this->regionId = System::REGION_EUROPE;
            $this->compId = 0;
        }
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

    public function save(string $file): void
    {
        if ($this->compId === 0) {
            throw new \RuntimeException('The competition id has not been set');
        }

        $json = json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
        file_put_contents($file, $json);
    }
}
