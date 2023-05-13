<?php declare(strict_types=1);

namespace Wprs\Api\Web;

class RequestInfo
{
    private int $count;
    private float $time;

    public function __construct(int $count, float $time)
    {
        $this->count = $count;
        $this->time = $time;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTime(): float
    {
        return $this->time;
    }
}
