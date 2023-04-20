<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers;

use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\Job;
use Wprs\Api\Http\Response;
use Wprs\Api\Http\WorkerInterface;

class MockWorker implements WorkerInterface
{
    /** @var array<Job> */
    private array $jobs = [];

    private string $contents;
    private ?int $statusCode = null;

    public function __construct(string $contents, ?int $statusCode)
    {
        $this->contents = $contents;
        $this->statusCode = $statusCode;
    }

    public function download(Job $job): void
    {
        $this->jobs[$job->id] = $job;
    }

    public function tick(): void
    {
        while (count($this->jobs) !== 0) {
            $job = array_shift($this->jobs);

            if ($this->statusCode !== null) {
                $job->status = HttpDownloader::STATUS_FAILED;
                $msg = sprintf('http status %d, url: %s', $this->statusCode, $job->url);
                throw new \RuntimeException($msg);
            }

            $job->status = HttpDownloader::STATUS_COMPLETED;
            $job->response = new Response($job->id, $job->url, $this->contents);
        }
    }

    public function abortRequest(Job $job): void
    {
        $this->removeFromJobs($job);
    }

    private function removeFromJobs(Job $job): void
    {
        if (isset($this->jobs[$job->id])) {
            unset($this->jobs[$job->id]);
        }
    }
}