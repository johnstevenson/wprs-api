<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

use Wprs\Api\Web\RequestInfo;

class Task
{
    private const PHASE_INIT = 0;
    private const PHASE_MAIN = 1;
    private const PHASE_EXTRA = 2;

    private int $phase;
    private float $startTime;

    /** @var array<string> */
    public array $urls = [];

    /** @var array<string> */
    public array $extraUrls = [];

    /** @var array<DataCollector|null> */
    public array $items = [];

    /** @var array<int> */
    public array $dataIndexes = [];

    /**
     * @param array<string> $urls
     */
    public function __construct(array $urls)
    {
        $this->phase = self::PHASE_INIT;

        foreach ($urls as $url) {
            $this->urls[] = $url;
            $this->items[] = null;
        }

        $this->startTime = microtime(true);
    }

    /**
     * @param array<string> $urls
     */
    public function addExtraUrls(array $urls, int $index): void
    {
        if ($this->phase > self::PHASE_MAIN) {
            throw new \RuntimeException($this->getPhaseError());
        }
        $dataCollector = $this->getDataCollector($index);

        if ($dataCollector === null) {
            throw new \RuntimeException('Data not set at index '. $index);
        }

        foreach ($urls as $url) {
            $this->extraUrls[] = $url;
            $this->dataIndexes[] = $index;
        }
    }

    public function getDataCollector(int $index): ?DataCollector
    {
        if ($this->phase === self::PHASE_EXTRA) {
            if (!array_key_exists($index, $this->dataIndexes)) {
                throw new \RuntimeException('Data not found at index '. $index);
            }

            $index = $this->dataIndexes[$index];
        }

        if (!array_key_exists($index, $this->items)) {
            throw new \RuntimeException('Data not found at index '. $index);
        }

        return $this->items[$index];
    }

    public function getRequestInfo(): RequestInfo
    {
        $count = count($this->urls) + count($this->extraUrls);
        $time = microtime(true) - $this->startTime;

        return new RequestInfo($count, $time);
    }

    /**
     * @return non-empty-array<DataCollector>
     */
    public function getResults(): array
    {
        $results = [];

        foreach ($this->items as $item) {
            if ($item === null) {
                break;
            }
            $results[] = $item;
        }

        $count = count($results);

        if ($count === 0 || $count !== count($this->items)) {
            throw new \RuntimeException('Results are incomplete');
        }

        return $results;
    }

    /**
     * @return array<string>
     */
    public function getUrls(): array
    {
        if ($this->phase === self::PHASE_INIT) {
            $this->phase = self::PHASE_MAIN;
            return $this->urls;
        }

        if ($this->phase === self::PHASE_MAIN) {
            $this->phase = self::PHASE_EXTRA;
            return $this->extraUrls;
        }

        throw new \RuntimeException($this->getPhaseError());

    }

    public function hasExtraUrls(): bool
    {
        return count($this->extraUrls) !== 0;
    }

    public function setDataCollector(DataCollector $dataCollector, int $index): void
    {
        if ($this->phase >= self::PHASE_EXTRA) {
            throw new \RuntimeException($this->getPhaseError());
        }

        if (!array_key_exists($index, $this->items)) {
            throw new \RuntimeException('Data collector not found at index '. $index);
        }

        $this->items[$index] = $dataCollector;
    }

    private function getPhaseError(): string
    {
        return 'Operation not valid in phase '.$this->phase;
    }
}
