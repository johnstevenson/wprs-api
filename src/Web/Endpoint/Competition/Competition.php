<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\FilterInterface;

class Competition extends Application
{
    public function __construct(
        int $activity,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new CompetitionParser();

        parent::__construct($activity, $parser, $filter, $downloader);
    }

    public function getData(string $rankingDate, $param): array
    {
        if (!($param instanceof CompetitionParams)) {
            if (!is_integer($param)) {
                throw new \RuntimeException('The competition id must be an integer.');
            }
            $params = new CompetitionParams($param);
        }

        $data = parent::run($rankingDate, $params);
        $meta = parent::getMeta();
        $details = array_merge($data->extras['details'], $params->getDetails());

        $result = [
            'meta' => $meta,
            'data' => [
                'details' => $details,
                'items' => $data->items,
            ],
        ];

        return $result;
    }
}
