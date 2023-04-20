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
        $rankingDate = System::getRankingDate($rankingDate);
        $results = $this->getBatch([$rankingDate], $regionId);

        return $results[0];
    }

    /**
     * @param array<string> $rankingDates
     * @phpstan-return non-empty-array<apiData>
     */
    public function getBatch(array $rankingDates, ?int $regionId): array
    {
        $results = [];
        $urls = [];
        $jobs = [];

        System::checkParams($rankingDates, System::PARAM_DATE);

        foreach ($rankingDates as $rankingDate) {
            $params = new NationsParams($regionId);
            $job = $this->getJob($rankingDate, $params);
            $urls[] = $job->getUrl();
            $jobs[] = $job;
        }

        $dataSets = parent::run($urls);

        foreach ($dataSets as $index => $data) {
            $job = $jobs[$index];
            // details are the params values plus count_ww from details
            $details = array_merge($job->getDetails(), $data->getExtrasItem('details'));
            $results[] = $job->getData($data, $details);
        }

        return $results;
    }
}
