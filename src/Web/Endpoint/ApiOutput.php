<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use Wprs\Api\Web\System;

/**
 * @phpstan-type apiMeta array{endpoint: string, discipline: string, ranking_date: string, count: int, version: string}
 * @phpstan-type apiDetails array<string, string|int>
 * @phpstan-type apiObject array<non-empty-array<string, string|int>>
 * @phpstan-type apiItem non-empty-array<string, string|int|apiObject>
 * @phpstan-type apiErrors array<string>
 * @phpstan-type apiData array{meta: apiMeta, data: array{details: apiDetails|null, items: array<apiItem>, errors: apiErrors|null}}
 */
class ApiOutput
{
    private string $endpoint;
    private string $discipline;
    private string $rankingDate;
    private string $version;

    public function __construct(string $endpoint, int $discipline, string $rankingDate)
    {
        $this->endpoint = System::getEndpoint($endpoint);
        $this->discipline = System::getDisciplineForDisplay($discipline);
        $this->rankingDate = $rankingDate;
        $this->version = System::getVersion();
    }

    /**
     * @phpstan-param apiDetails $details
     * @phpstan-return apiData
     */
    public function getData(DataCollector $dataCollector, ?array $details): array
    {
        $meta = $this->getMeta($dataCollector);
        $errors = $dataCollector->getErrors();

        return [
            'meta' => $meta,
            'data' => [
                'details' => $details,
                'items' => $dataCollector->getItems(),
                'errors' => count($errors) !== 0 ? $errors : null,
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
            'updated' => $dataCollector->getUpdated(),
            'count' => $dataCollector->getItemCount(),
            'version' => $this->version,
        ];
    }
}
