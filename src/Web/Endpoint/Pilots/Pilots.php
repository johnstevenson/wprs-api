<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Pilots;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;

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
    public function getData(?string $rankingDate, int $regionId, ?int $nationId = null, ?int $scoring = null): array
    {
        $params = new PilotsParams($regionId, $nationId, $scoring);
        $data = parent::run($rankingDate, $params);
        $details = $params->getDetails();

        // Add nation name if nation id was requested
        if (isset($details['nation']) && isset($data->items[0]['nation'])) {
            // for phpstan
            if (is_scalar($data->items[0]['nation'])) {
                $details['nation'] = (string) $data->items[0]['nation'];
            }
        }

        return parent::getOutput($details);
    }

    public function getCount(?string $rankingDate, int $regionId, ?int $nationId = null, ?int $scoring = null): int
    {
        $this->setRestricted();
        $params = new PilotsParams($regionId, $nationId, $scoring);
        $data = parent::run($rankingDate, $params);

        return $data->overallCount;
    }
}
