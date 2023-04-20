<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\System;

/**
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class Competitions extends Application
{
    public function __construct(
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new CompetitionsParser();

        parent::__construct($discipline, $parser, $filter, $downloader);
    }

    /**
     * @phpstan-return apiData
     */
    public function getData(?string $rankingDate = null): array
    {
        $rankingDate = System::getRankingDate($rankingDate);
        $results = $this->getBatch([$rankingDate]);

        return $results[0];
    }

    /**
     * @param array<string> $rankingDates
     * @phpstan-return non-empty-array<apiData>
     */
    public function getBatch(array $rankingDates): array
    {
        $results = [];
        $urls = [];
        $jobs = [];

        System::checkParams($rankingDates, System::PARAM_DATE);

        foreach ($rankingDates as $rankingDate) {
            $params = new CompetitionsParams();
            $job = $this->getJob($rankingDate, $params);
            $urls[] = $job->getUrl();
            $jobs[] = $job;
        }

        $dataSets = parent::run($urls);

        foreach ($dataSets as $index => $data) {
            $job = $jobs[$index];
            $results[] = $job->getData($data);
        }

        return $results;
    }
}
