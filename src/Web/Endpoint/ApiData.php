<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use Wprs\Api\Web\Rank;

class ApiData
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

    public function getOuput(DataCollector $dataCollector, ?array $details)
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

    private function getMeta(DataCollector $dataCollector)
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