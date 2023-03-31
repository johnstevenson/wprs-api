<?php declare(strict_types=1);

namespace Wprs\Api\Web\Endpoint;

/**
 * @phpstan-import-type apiDetails from \Wprs\Api\Web\Endpoint\ApiOutput
 * @phpstan-import-type apiData from \Wprs\Api\Web\Endpoint\ApiOutput
 */
class Job
{
    private string $url;

    /** @phpstan-var apiDetails */
    private array $details;
    private ApiOutput $output;

    /**
     * @phpstan-param apiDetails $details
     */
    public function __construct(string $url, array $details, ApiOutput $output)
    {
        $this->url = $url;
        $this->details = $details;
        $this->output = $output;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @phpstan-return apiDetails $details
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @phpstan-param apiDetails $details
     * @phpstan-return apiData
     */
    public function getData(DataCollector $dataCollector, ?array $details = null): array
    {
        return $this->output->getData($dataCollector, $details);
    }
}