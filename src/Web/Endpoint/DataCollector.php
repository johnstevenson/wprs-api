<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

/**
 * @phpstan-type dataExtra array<string, string|int>
 * @phpstan-import-type apiItem from ApiOutput
 * @phpstan-import-type apiErrors from ApiOutput
 */
class DataCollector
{
    private int $overallCount = 0;
    private int $itemCount = 0;
    private int $filteredCount = 0;
    private ?string $updated;
    /** @phpstan-var array<apiItem> */
    private array $items = [];
    /** @phpstan-var array<string, dataExtra> */
    private array $extras = [];
    /** @phpstan-var apiErrors */
    private array $errors = [];

    public function __construct(int $overallCount, ?string $updated = null)
    {
        $this->overallCount = $overallCount;
        $this->updated = $updated;
    }

    /**
     * @phpstan-param apiItem $item
     * @phpstan-param apiErrors $errors
     */
    public function addItem(array $item, ?array $errors, ?FilterInterface $filter = null): void
    {
        if ($filter !== null) {
            $item = $filter->filter($item, $errors);

            if ($item === null) {
                ++$this->filteredCount;
                return;
            }
        }

        $this->items[] = $item;
        ++$this->itemCount;

        if ($errors !== null) {
            $index = $this->itemCount - 1;

            foreach ($errors as $error) {
                $this->errors[] = Utils::makeItemError($error, $index);
            }
        }
    }

    /**
     * Merges data from additional pages
     */
    public function addData(DataCollector $data): void
    {
        foreach ($data->getItems() as $item) {
            $this->items[] = $item;
            ++$this->itemCount;
        }

        foreach ($data->getErrors() as $error) {
            $this->errors[] = $error;
        }
    }

    /**
     * @phpstan-param dataExtra $item
     * @phpstan-param apiErrors $errors
     */
    public function addExtra(string $key, array $item, ?array $errors = null): void
    {
        $this->extras[$key] = $item;

        if ($errors !== null) {
            foreach ($errors as $error) {
                $this->errors[] = $error;
            }
        }
    }

    /**
     * @phpstan-return apiErrors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @phpstan-return array<string, dataExtra>
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * @phpstan-return dataExtra
     */
    public function getExtrasItem(string $key): array
    {
        return $this->extras[$key] ?? [];
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    /**
     * @phpstan-return array<apiItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getOverallCount(): int
    {
        return $this->overallCount;
    }

    public function getProcessedCount(): int
    {
        return $this->itemCount + $this->filteredCount;
    }

    public function getUpdated(): string
    {
        return (string) $this->updated;
    }
}
