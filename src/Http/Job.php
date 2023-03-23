<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class Job
{
    public int $id;
    public int $status;
    public string $url;
    public array $options;
    public int $curlId;
    public $curlHandle;
    public $bodyHandle;
    public int $retries;
    public Response $response;

    public function __construct(int $id, string $url, array $options)
    {
        $this->status = HttpDownloader::STATUS_QUEUED;
        $this->id = $id;
        $this->url = $url;
        $this->options = $options;
        $this->retries = 0;
    }
}
