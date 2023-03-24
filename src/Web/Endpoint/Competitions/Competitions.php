<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint\Competitions;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Web\Application;
use Wprs\Api\Web\Endpoint\FilterInterface;

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

    public function getData(?string $rankingDate = null): array
    {
        $params = new CompetitionsParams();
        $data = parent::run($rankingDate, $params);

        return parent::formatOutput(null);
    }
}
