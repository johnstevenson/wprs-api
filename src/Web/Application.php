<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Http\HttpDownloader;
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
    /**
     * @var array<int, mixed>
     */
    private array $options = [];
    private bool $restricted = false;

    private ParserInterface $parser;
    private ?FilterInterface $filter;
    private DownloaderInterface $downloader;

    public function __construct(
        int $discipline,
        ParserInterface $parser,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $this->discipline = $discipline;
        $this->parser = $parser;
        $this->filter = $filter;
        $this->downloader = $downloader ?? new HttpDownloader();

        $this->endpoint = get_class($this);
        $this->path = System::getPath($discipline, $this->endpoint);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): self
    {
        $curlOptions = $options['curl'] ?? null;

        if (is_array($curlOptions)) {
            $this->options = $curlOptions;
            unset($options['curl']);
        }

        if (count($options) !== 0 && $this->filter !==  null) {
            $this->filter->setOptions($options);
        }

        return $this;
    }

    public function setRestricted(): self
    {
        $this->restricted = true;

        return $this;
    }

    /**
     * @param non-empty-array<string> $urls
     * @return array<DataCollector>
     */
    protected function run(array $urls): array
    {
        $task = new Task($urls);
        $this->runTask($task);

        if ($task->hasExtraUrls()) {
            $this->runTask($task);
        }

        return $task->getResults();
    }

    protected function getJob(?string $rankingDate, ParamsInterface $params, ?string $path = null): Job
    {
        $rankingDate = System::getRankingDate($rankingDate);
        $query = $params->getQueryParams($rankingDate);
        $url = $this->buildUrl($query, $path);

        $output = new ApiOutput($this->endpoint, $this->discipline, $rankingDate);

        return new Job($url, $params->getDetails(), $output);
    }

    private function runTask(Task $task): void
    {
        try {
            $responses = $this->downloader->getBatch($task->getUrls(), $this->options);
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
            $contents = $this->getHtmlFromResponse($response);
            $data = $this->parser->parse($contents, $this->filter);

            $dataCollector = $task->getDataCollector($index);

            if ($dataCollector === null) {
                $task->setDataCollector($data, $index);
                $urls = $this->getRemainingUrls($data, $response->url, $this->restricted);

                if (count($urls) !== 0) {
                    $task->addExtraUrls($urls, $index);
                }
                continue;
            }

            foreach ($data->items as $item) {
                $dataCollector->add($item);
            }
        }
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

        $itemCount = $collector->itemCount + $collector->filteredCount;

        if ($itemCount === $collector->overallCount) {
            return $result;
        }

        $more = (int) (ceil($collector->overallCount / $itemCount) - 1);

        for ($i = 0; $i < $more; ++$i) {
            $page = $i + 2;
            $result[] = $url.'&page='.$page;
        }

        return $result;
    }

    private function getHtmlFromResponse(Response $response): string
    {
        $result = $response->contents;

        // Check for gzip magic number
        if ("\x1f\x8b" === substr($result, 0, 2)) {
            $result = (string) gzdecode($result);
        }

        return $result;
    }

    private function isMultiPageEndpoint(): bool
    {
        // Currently this is the only endpoint that needs multiple pages
        return $this->endpoint === System::ENDPOINT_PILOTS;
    }


    /**
     * @param non-empty-array<string, string> $params
     */
    private function buildUrl(array $params, ?string $path): string
    {
        $path = $path ?? $this->path;
        $pairs = [];

        foreach ($params as $name => $value) {
            $name = $this->queryStringEncode($name);
            $value = $this->queryStringEncode($value);
            $pairs[] = $name.'='.$value;
        }

        $query = implode('&', $pairs);

        return $path.'?'.$query;
    }

    /**
     * Encodes a query string as per whatwg.org Url standard
     *
     * https://url.spec.whatwg.org/
     *
     * @param string $value
     * @return string
     */
    private function queryStringEncode(string $value): string
    {
        $result = '';

        // query percent-encode set: x22 (") 34, x23 (#) 35, x3C (<) 60, x3E (>) 62
        $encodeSet = [34, 35, 60, 62];

        for ($i = 0, $len = strlen($value); $i < $len; ++$i) {
            $c = $value[$i];
            $dec = ord($c);

            // C0 control or space, upper ascii bound x7E (~) 126
            if ($dec < 33 || $dec > 126 || in_array($dec, $encodeSet, true)) {
                $c = '%'.bin2hex($c);
            }

            $result .= $c;
        }

        return $result;
    }
}
