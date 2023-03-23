<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\FilterInterface;

class Competition extends Application
{
    public function __construct(
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new CompetitionParser();

        parent::__construct($discipline, $parser, $filter, $downloader);
    }

    public function getData(string $rankingDate, $params): array
    {
        if (!($params instanceof CompetitionParams)) {
            if (!is_integer($params)) {
                throw new \RuntimeException('The competition id must be an integer.');
            }
            $params = new CompetitionParams($params);
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
