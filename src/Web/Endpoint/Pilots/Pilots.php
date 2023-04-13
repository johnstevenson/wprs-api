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
        $params = new PilotsParams($regionId, $nationId, $scoring);
        $job = $this->getJob($rankingDate, $params);

        $results = parent::run([$job->getUrl()]);

        /** @var \Wprs\Api\Web\Endpoint\DataCollector */
        $data = $results[0];

        // details are the params values
        $details = $job->getDetails();
        $items = $data->getItems();

        // Add nation name if nation id was requested
        if ($nationId !== null && isset($items[0]['nation'])) {
            // for phpstan
            if (is_string($items[0]['nation'])) {
                $details['nation'] = $items[0]['nation'];
            }
        }

        return $job->getData($data, $details);
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
