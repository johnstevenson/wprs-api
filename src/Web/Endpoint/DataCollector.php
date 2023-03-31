<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

/**
 * @phpstan-import-type apiItem from ApiOutput
 */
class DataCollector
{
    public int $overallCount = 0;
    public int $itemCount = 0;
    public int $filteredCount = 0;
    /** @phpstan-var array<apiItem> */
    public array $items = [];
    /** @var array<string, array<string, string|int>> */
    public array $extras = [];

    /** @var array<string> */
    public array $errors = [];

    public function __construct(int $overallCount)
    {
        $this->overallCount = $overallCount;
    }

    /**
     * @phpstan-param apiItem $item
     */
    public function add(array $item, ?FilterInterface $filter = null): void
    {
        if ($filter !== null) {
            $item = $filter->filter($item);

            if ($item === null) {
                ++$this->filteredCount;
                return;
            }
        }

        $this->items[] = $item;
        ++$this->itemCount;
    }

    /**
     * @param array<string, string|int> $item
     */
    public function addExtra(string $key, array $item): void
    {
        $this->extras[$key] = $item;
    }

    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}
