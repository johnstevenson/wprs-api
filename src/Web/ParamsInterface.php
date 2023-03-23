<?php declare(strict_types=1);

namespace Wprs\Api\Web;

interface ParamsInterface
{
    public function getQueryParams(string $rankingDate): array;

    public function getDetails(): array;
}
