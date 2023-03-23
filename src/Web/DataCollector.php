<?php declare(strict_types=1);

namespace Wprs\Api\Web;

class DataCollector
{
    public int $overallCount = 0;
    public int $itemCount = 0;
    public int $filteredCount = 0;
    public array $items = [];
    public array $extras = [];

    public function __construct(int $overallCount)
    {
        $this->overallCount = $overallCount;
    }

    public function add(array $item, ?FilterInterface $filter = null): void
    {
        if (null !== $filter) {
            $item = $filter->filter($item);

            if (null === $item) {
                ++$this->filteredCount;
                return;
            }
        }

        $this->items[] = $item;
        ++$this->itemCount;
    }

    public function addExtra(string $key, $item): void
    {
        $this->extras[$key] = $item;
    }
}
