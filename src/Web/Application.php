<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\Response;
use Wprs\Api\Web\Endpoint\ApiOutput;
use Wprs\Api\Web\Endpoint\DataCollector;
use Wprs\Api\Web\Endpoint\FilterInterface;
use Wprs\Api\Web\Endpoint\ParamsInterface;
use Wprs\Api\Web\Endpoint\ParserInterface;

/**
 * @phpstan-import-type apiDetails from \Wprs\Api\Web\Endpoint\ApiOutput
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
abstract class Application
{
    private int $discipline;
    private int $endpoint;
    private string $path;
    /**
     * @var array<int, mixed>
     */
    private array $options = [];
    private bool $restricted = false;

    private ParserInterface $parser;
    private ?FilterInterface $filter;
    private DownloaderInterface $downloader;
    private ApiOutput $output;
    private DataCollector $dataCollector;

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

        $this->endpoint = $this->getEndpoint();
        $this->path = Rank::getPath($discipline, $this->endpoint);
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

    protected function run(?string $rankingDate, ParamsInterface $params): DataCollector
    {
        $this->dataCollector = new DataCollector(DataCollector::PLACE_HOLDER);
        $rankingDate = $this->checkDateFormat($rankingDate);
        $this->output = new ApiOutput($this->endpoint, $this->discipline, $rankingDate);

        $qs = $this->buildQuery($params, $rankingDate);
        $url = $this->path.'?'.$qs;

        $response = $this->downloader->get($url, $this->options);
        $this->processResponses([$response]);

        $urls = $this->getRemainingUrls($this->dataCollector, $url, $this->restricted);

        if (count($urls) !== 0) {
            $responses = $this->downloader->getBatch($urls, $this->options);
            $this->processResponses($responses);
        }

        return $this->dataCollector;
    }

    /**
     * @param apiDetails $details
     * @return apiData
     */
    protected function getOutput(?array $details): array
    {
        if ($this->dataCollector->isPlaceholder()) {
            throw new \RuntimeException('Data has not been generated');
        }

        return $this->output->getData($this->dataCollector, $details);
    }

    private function checkDateFormat(?string $rankingDate): string
    {
        $tz = new \DateTimeZone('UTC');

        if (null === $rankingDate) {
            $date = new \DateTime('now', $tz);
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $rankingDate, $tz);
        }

        if (false === $date) {
            throw new \RuntimeException('Invalid rankingDate: '.$rankingDate);
        }

        return $date->format('Y-m-01');
    }

    /**
     * @param array<Response> $responses
     */
    private function processResponses(array $responses): void
    {
        foreach ($responses as $response) {
            $contents = $this->getHtmlFromResponse($response);
            $data = $this->parser->parse($contents, $this->filter);

            if ($this->dataCollector->isPlaceholder()) {
                $this->dataCollector = $data;
                return;
            }

            foreach ($data->items as $item) {
                $this->dataCollector->add($item);
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

    private function getEndpoint(): int
    {
        $class = strtr(get_class($this), '\\', '/');
        $name = basename($class);

        return Rank::getEndpointFromName($name);
    }

    private function isMultiPageEndpoint(): bool
    {
        // Currently this is the only endpoint that needs multiple pages
        return $this->endpoint === Rank::ENDPOINT_PILOTS;
    }

    private function buildQuery(ParamsInterface $params, string $rankingDate): string
    {
        $items = $params->getQueryParams($rankingDate);
        $pairs = [];

        foreach ($items as $name => $value) {
            $name = $this->queryStringEncode($name);
            $value = $this->queryStringEncode($value);
            $pairs[] = $name.'='.$value;
        }

        return implode('&', $pairs);
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
