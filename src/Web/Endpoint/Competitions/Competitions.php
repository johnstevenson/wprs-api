<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\FilterInterface;

class Competitions extends Application
{
    private CompetitionsParams $params;

    public function __construct(
        int $activity,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $this->params = new CompetitionsParams();
        $parser = new CompetitionsParser();

        parent::__construct($activity, $parser, $filter, $downloader);
    }

    public function getData(?string $rankingDate): array
    {
        $data = parent::run($rankingDate, $this->params);
        $meta = parent::getMeta();

        $result = [
            'meta' => $meta,
            'data' => [
                'details' => null,
                'items' => $data->items
            ],
        ];

        return $result;
    }

    private function setMeta(int $activity, string $rankingDate): void
    {
        $result = [
            'endpoint' => Ranking::getEndpoint($this->endpoint),
            'activity' => Ranking::getActivity($activity),
            ''


        ];
    }
}
