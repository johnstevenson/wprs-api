<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Nations;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\Job;
use Wprs\Api\Web\System;

/**
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class Nations extends Application
{
    public function __construct(
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new NationsParser();

        parent::__construct($discipline, $parser, $filter, $downloader);
    }

    /**
     * @phpstan-return apiData
     */
    public function getData(?string $rankingDate, ?int $regionId): array
    {
        $params = new NationsParams($regionId);
        $job = $this->getJob($rankingDate, $params);

        $results = parent::run([$job->getUrl()]);

        /** @var \Wprs\Api\Web\Endpoint\DataCollector */
        $data = $results[0];

        // details are the params values plus count_ww from details
        $details = array_merge($job->getDetails(), $data->getExtrasItem('details'));
        $items = $data->getItems();

        return $job->getData($data, $details);
    }
}
