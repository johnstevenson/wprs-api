<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use Wprs\Api\Web\Endpoint\ParamsInterface;

class CompetitionParams implements ParamsInterface
{
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @phpstan-return non-empty-array<string, string>
     */
    public function getQueryParams(string $rankingDate): array
    {
        $params = [
            'rankingDate' => $rankingDate,
            'id' => (string) $this->id,
        ];

        return $params;
    }

    /**
     * @phpstan-return non-empty-array<string, int>
     */
    public function getDetails(): array
    {
        $meta = [
            'id' => $this->id,
        ];

        return $meta;
    }
}
