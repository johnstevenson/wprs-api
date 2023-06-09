<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\HttpUtils;
use Wprs\Api\Http\Response;
use Wprs\Api\Web\Endpoint\ApiOutput;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\Job;
use Wprs\Api\Web\Endpoint\ParamsInterface;
use Wprs\Api\Web\Endpoint\ParserInterface;
use Wprs\Api\Web\Endpoint\Task;

/**
 * @phpstan-import-type apiDetails from \Wprs\Api\Web\Endpoint\ApiOutput
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
abstract class Application
{
    private int $discipline;
    private string $endpoint;
    private string $path;
    private bool $restricted = false;

    private ParserInterface $parser;
    private ?FilterInterface $filter;
    private DownloaderInterface $downloader;
    private RequestInfo $requestInfo;

    public function __construct(
        int $discipline,
        ParserInterface $parser,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $this->discipline = $discipline;
        $this->parser = $parser;
        $this->filter = $filter;
        $this->downloader = $downloader ?? new HttpDownloader(System::getUserAgent());

        $this->requestInfo = new RequestInfo(0, 0.0);
        $this->endpoint = get_class($this);
        $this->path = System::getPath($discipline, $this->endpoint);
    }

    public function getRequestInfo(): RequestInfo
    {
        return $this->requestInfo;
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    public function setCurlOptions(array $curlOptions): self
    {
        $this->downloader->setCurlOptions($curlOptions);

        return $this;
    }

    /**
     * @internal
     */
    public function setRestricted(): self
    {
        $this->restricted = true;

        return $this;
    }

    /**
     * @param array<string> $urls
     * @return non-empty-array<DataCollector>
     */
    protected function run(array $urls): array
    {
        if (count($urls) === 0) {
            $message = System::formatMessage(static::class, 'error, no urls');
            throw new WprsException($message);
        }

        $task = new Task($urls);
        $this->runTask($task);

        if ($task->hasExtraUrls()) {
            $this->runTask($task);
        }

        $this->requestInfo = $task->getRequestInfo();

        return $task->getResults();
    }

    protected function getJob(?string $rankingDate, ParamsInterface $params, ?string $path = null): Job
    {
        $rankingDate = System::getRankingDate($rankingDate);
        $query = $params->getQueryParams($rankingDate);
        $path = $path ?? $this->path;
        $url = HttpUtils::buildQuery($query, $path);

        $output = new ApiOutput($this->endpoint, $this->discipline, $rankingDate);

        return new Job($url, $params->getDetails(), $output);
    }

    private function runTask(Task $task): void
    {
        try {
            $responses = $this->downloader->getBatch($task->getUrls());
        } catch (\RuntimeException $e) {
            throw new WprsException('Download error:', $e);
        }

        try {
            $this->handleResponses($task, $responses);
        } catch (\RuntimeException $e) {
            throw new WprsException('Response error:', $e);
        }
    }

    /**
     * @param array<Response> $responses
     */
    private function handleResponses(Task $task, array $responses): void
    {
        foreach ($responses as $index => $response) {
            $data = $this->getData($response);
            $dataCollector = $task->getDataCollector($index);

            if ($dataCollector === null) {
                $task->setDataCollector($data, $index);
                $urls = $this->getRemainingUrls($data, $response->url, $this->restricted);

                if (count($urls) !== 0) {
                    $task->addExtraUrls($urls, $index);
                }
                continue;
            }

            $dataCollector->addData($data);
        }
    }

    private function getData(Response $response): DataCollector
    {
        $contents = HttpUtils::getResponseContent($response);

        try {
            $result = $this->parser->parse($contents, $this->filter);
        } catch(WprsException $e) {
            $message = sprintf('%s.', $response->url);
            throw new WprsException($message);
        }

        return $result;
    }

    /**
     * @return array<string>
     */
    private function getRemainingUrls(DataCollector $collector, string $url, bool $restricted): array
    {
        $result = [];

        if (!$this->isMultiPageEndpoint() || $restricted) {
            return $result;
        }

        $itemCount = $collector->getProcessedCount();

        if ($itemCount === $collector->getOverallCount()) {
            return $result;
        }

        $more = (int) (ceil($collector->getOverallCount() / $itemCount) - 1);

        for ($i = 0; $i < $more; ++$i) {
            $page = $i + 2;
            $result[] = $url.'&page='.$page;
        }

        return $result;
    }

    private function isMultiPageEndpoint(): bool
    {
        // Currently this is the only endpoint that needs multiple pages
        return $this->endpoint === System::ENDPOINT_PILOTS;
    }
}
