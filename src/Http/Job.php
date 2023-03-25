<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class Job
{
    public int $id;
    public int $status;
    public string $url;
    /**
     * @var array<string, mixed>
     */
    public array $options;
    public int $curlId;
    /**
     * @var \CurlHandle|null
     */
    public $curlHandle;
    /**
     * @var Resource|null
     */
    public $bodyHandle;
    public int $retries;
    public Response $response;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(int $id, string $url, array $options)
    {
        $this->status = HttpDownloader::STATUS_QUEUED;
        $this->id = $id;
        $this->url = $url;
        $this->options = $options;
        $this->retries = 0;
    }
}
