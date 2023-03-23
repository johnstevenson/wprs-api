<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Http\DownloaderInterface;
use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\Response;

abstract class Application
{
    private int $endpoint;
    private string $path;
    private array $options = [];
    private bool $restricted = false;
    private array $meta;

    private ParserInterface $parser;
    private ?FilterInterface $filter;
    private DownloaderInterface $downloader;
    private ?DataCollector $dataCollector;

    public function __construct(
        int $activity,
        ParserInterface $parser,
        ?FilterInterface $filter = null,
        ?DownloaderInterface $downloader = null
    ) {
        $this->parser = $parser;
        $this->filter = $filter;
        $this->downloader = $downloader ?? new HttpDownloader();

        $this->endpoint = $this->getEndpoint();
        $this->path = Ranking::getPath($activity, $this->endpoint);
        $this->meta = Ranking::getMeta($activity, $this->endpoint);
    }

    public function run(?string $rankingDate, ParamsInterface $params): DataCollector
    {
        $this->dataCollector = null;
        $rankingDate = $this->checkDateFormat($rankingDate);
        $this->meta['ranking_date'] = $rankingDate;

        $qs = $this->buildQuery($params, $rankingDate);
        $url = $this->path.'?'.$qs;

        $response = $this->downloader->get($url, $this->options);
        $this->processResponses([$response]);

        $urls = $this->getRemainingUrls($this->dataCollector, $url, $this->restricted);

        if (!empty($urls)) {
            $responses = $this->downloader->getBatch($urls, $this->options);
            $this->processResponses($responses);
        }

        return $this->dataCollector;
    }

    public function setOptions(array $options): self
    {
        $curlOptions = $options['curl'] ?? null;

        if (null !== $curlOptions) {
            $this->options['curl'] = $curlOptions;
            unset($options['curl']);
        }

        if (!empty($options) && null !== $this->filter) {
            $this->filter->setOptions($options);
        }

        return $this;
    }

    public function setRestricted(): self
    {
        $this->restricted = true;

        return $this;
    }

    protected function getMeta(): array
    {
        if (null === $this->dataCollector) {
            throw new \RuntimeException('Meta data has not been generated');
        }

        $this->meta['count'] = $this->dataCollector->itemCount;

        return $this->meta;
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

    private function processResponses(array $responses): void
    {
        foreach ($responses as $response) {
            $contents = $this->getHtmlFromResponse($response);
            $data = $this->parser->parse($contents, $this->filter);

            if (null === $this->dataCollector) {
                $this->dataCollector = $data;
                return;
            }

            foreach ($data->items as $item) {
                $this->dataCollector->add($item);
            }
        }
    }

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
            $result = gzdecode($result);
        }

        return $result;
    }

    private function getEndpoint(): int
    {
        $class = strtr(get_class($this), '\\', '/');
        $name = basename($class);

        return Ranking::getEndpointFromName($name);
    }

    private function isMultiPageEndpoint()
    {
        // Currently this is the only endpoint that needs multiple pages
        return $this->endpoint === Ranking::ENDPOINT_PILOTS;
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
