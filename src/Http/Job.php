<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class Job
{
    public int $id;
    public int $status;
    public string $url;
    /**
     * @var array<int, mixed>
     */
    public array $curlOptions;
    public int $curlId;
    /**
     * @var \CurlHandle|null
     */
    public $curlHandle;
    /**
     * @var Resource
     */
    public $bodyHandle;
    public int $retries;
    public Response $response;

    /**
     * @param array<int, mixed> $curlOptions
     */
    public function __construct(int $id, string $url, array $curlOptions)
    {
        $this->status = HttpDownloader::STATUS_QUEUED;
        $this->id = $id;
        $this->url = $url;
        $this->curlOptions = $curlOptions;
        $this->retries = 0;
    }
}
