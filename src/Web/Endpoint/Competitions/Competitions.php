<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;

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
        $params = new CompetitionsParams();
        $job = $this->getJob($rankingDate, $params);

        $results = parent::run([$job->getUrl()]);
        $data = $results[0];

        return $job->getData($data);
    }
}
