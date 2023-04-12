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
        $results = $this->getBatch($rankingDate, [$id]);

        return $results[0];
    }

    /**
     * @param array<int> $ids
     * @phpstan-return non-empty-array<apiData>
     */
    public function getBatch(string $rankingDate, array $ids): array
    {
        $results = [];
        $urls = [];
        $jobs = [];

        $this->checkValues($ids);

        foreach ($ids as $id) {
            $params = new CompetitionParams($id);
            $job = $this->getJob($rankingDate, $params);
            $urls[] = $job->getUrl();
            $jobs[] = $job;
        }

        $dataSets = parent::run($urls);

        foreach ($dataSets as $index => $data) {
            $job = $jobs[$index];
            // Add competition id to details
            $details = array_merge($data->getExtrasItem('details'), $job->getDetails());
            $results[] = $job->getData($data, $details);
        }

        return $results;
    }

    /**
     * @param array<mixed> $ids
     */
    private function checkValues(array $ids): void
    {
        $idCount = count($ids);

        if ($idCount === 0) {
            throw new \RuntimeException('Missing ids');
        }

        foreach ($ids as $id) {
            if (!is_int($id)) {
                throw new \RuntimeException('id must be an int, got '.gettype($id));
            }
        }

        $unique = array_unique($ids);

        if (count($unique) !== $idCount) {
            throw new \RuntimeException('Duplicate ids');
        }
    }
}
