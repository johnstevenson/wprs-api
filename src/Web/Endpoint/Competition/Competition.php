<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competition;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;

/**
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
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

    /**
     * @phpstan-return apiData
     */
    public function getData(string $rankingDate, int $id): array
    {
        $params = new CompetitionParams($id);
        $data = parent::run($rankingDate, $params);

        // Add competition id to details
        $details = array_merge($data->extras['details'], $params->getDetails());

        return parent::getOutput($details);
    }
}
