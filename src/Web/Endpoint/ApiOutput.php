<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use Wprs\Api\Web\Rank;

/**
 * @phpstan-type apiMeta array{endpoint: string, discipline: string, ranking_date: string, count: int, version: string}
 * @phpstan-type apiDetails array<string, string|int>|null
 * @phpstan-type apiItem non-empty-array<mixed, mixed>
 * @phpstan-type apiData array{meta: apiMeta, data: array{details: apiDetails, items: array<apiItem>}}
 */
class ApiOutput
{
    private string $endpoint;
    private string $discipline;
    private string $rankingDate;
    private string $version;

    public function __construct(int $endpoint, int $discipline, string $rankingDate)
    {
        $this->endpoint = Rank::getEndpoint($endpoint);
        $this->discipline = Rank::getDiscipline($discipline);
        $this->rankingDate = $rankingDate;
        $this->version = Rank::getVersion();
    }

    /**
     * @phpstan-param apiDetails $details
     * @phpstan-return apiData
     */
    public function getData(DataCollector $dataCollector, ?array $details): array
    {
        $meta = $this->getMeta($dataCollector);

        return [
            'meta' => $meta,
            'data' => [
                'details' => $details,
                'items' => $dataCollector->items,
            ],
        ];
    }

    /**
     * @phpstan-return apiMeta
     */
    private function getMeta(DataCollector $dataCollector): array
    {
        return [
            'endpoint' => $this->endpoint,
            'discipline' => $this->discipline,
            'ranking_date' => $this->rankingDate,
            'count' => $dataCollector->itemCount,
            'version' => $this->version,
        ];
    }
}