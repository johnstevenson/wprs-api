<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\FilterInterface;

class Pilots extends Application
{
    public function __construct(
        int $activity,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new PilotsParser();

        parent::__construct($activity, $parser, $filter, $downloader);
    }

    public function getData(?string $rankingDate, PilotsParams $params): array
    {
        $data = parent::run($rankingDate, $params);
        $meta = parent::getMeta();
        $details = $params->getMeta();

        if (isset($details['nation']) && isset($data->items[0]['nation'])) {
            $details['nation'] = $data->items[0]['nation'];
        }

        $result = [
            'meta' => $meta,
            'data' => [
                'details' => $details,
                'items' => $data->items
            ],
        ];

        return $result;
    }

    public function getOverallCount(?string $rankingDate, PilotsParams $params): int
    {
        $this->setRestricted();
        $data = parent::run($rankingDate, $params);

        return $data->overallCount;
    }
}
