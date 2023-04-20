<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\Job;
use Wprs\Api\Web\System;

/**
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class Pilots extends Application
{
    public function __construct(
        int $discipline,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $parser = new PilotsParser();

        parent::__construct($discipline, $parser, $filter, $downloader);
    }

    /**
     * @phpstan-return apiData
     */
    public function getData(?string $rankingDate, ?int $regionId, ?int $nationId = null, ?int $scoring = null): array
    {
        $rankingDate = System::getRankingDate($rankingDate);
        $results = $this->getBatch([$rankingDate], $regionId, $nationId, $scoring);

        return $results[0];
    }

    /**
     * @param array<string> $rankingDates
     * @phpstan-return non-empty-array<apiData>
     */
    public function getBatch(array $rankingDates, ?int $regionId, ?int $nationId = null, ?int $scoring = null): array
    {
        $results = [];
        $urls = [];
        $jobs = [];

        System::checkParams($rankingDates, System::PARAM_DATE);

        foreach ($rankingDates as $rankingDate) {
            $params = new PilotsParams($regionId, $nationId, $scoring);
            $job = $this->getJob($rankingDate, $params);
            $urls[] = $job->getUrl();
            $jobs[] = $job;
        }

        $dataSets = parent::run($urls);

        foreach ($dataSets as $index => $data) {
            $job = $jobs[$index];

            // details are the params values
            $details = $job->getDetails();
            $items = $data->getItems();

            // add nation name if nation id was requested
            if ($nationId !== null && isset($items[0]['nation'])) {
                // for phpstan
                if (is_string($items[0]['nation'])) {
                    $details['nation'] = $items[0]['nation'];
                }
            }

            $results[] = $job->getData($data, $details);
        }

        return $results;
    }

    public function getCount(?string $rankingDate, int $regionId, ?int $nationId = null, ?int $scoring = null): int
    {
        $params = new PilotsParams($regionId, $nationId, $scoring);
        $job = $this->getJob($rankingDate, $params);
        $this->setRestricted();

        $results = parent::run([$job->getUrl()]);

        /** @var \Wprs\Api\Web\Endpoint\DataCollector */
        $data = $results[0];
        return $data->getOverallCount();
    }
}
