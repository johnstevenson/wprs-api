<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class HttpDownloader implements DownloaderInterface
{
    public const STATUS_QUEUED = 1;
    public const STATUS_STARTED = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_FAILED = 4;
    public const STATUS_ABORTED = 5;

    /** @var array<Job> */
    private array $jobs = [];
    private int $runningJobs = 0;
    private int $maxJobs = 12;
    /**
     * @var array<int, mixed>
     */
    private array $options;

    private ResponseCollector $responseCollector;
    private HttpWorker $httpWorker;

    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('curl extension is missing');
        }
        $this->responseCollector = new ResponseCollector();
        $this->httpWorker = new HttpWorker();
    }

    /**
     * @param array<int, mixed> $options
     */
    public function get(string $url, array $options = []): Response
    {
        $this->options = $this->formatOptions($options);
        $this->queueJob(0, $url, $this->options);
        $this->wait();

        return $this->responseCollector->getAll()[0];

    }

    /**
     * @param array<string> $urls
     * @param array<int, mixed> $options
     */
    public function getBatch(array $urls, array $options = []): array
    {
        $this->options = $this->formatOptions($options);
        $last = count($urls);
        $max = 50;
        $count = 0;

        foreach ($urls as $index => $url) {
            ++$count;

            $this->queueJob($index, $url, $this->options);

            if (0 === $count % $max || $count === $last) {
                $this->wait();

                if ($count < $last) {
                    usleep(50000);
                }
            }
        }

        return $this->responseCollector->getAll();
    }

    /**
     * @param array<int, mixed> $options
     */
    private function queueJob(int $index, string $url, array $options): void
    {
        $job = new Job($index, $url, $options);
        $this->jobs[$index] = $job;

        if ($this->runningJobs < $this->maxJobs) {
            $this->startJob($job);
        }
    }

    private function countActiveJobs(): int
    {
        if ($this->runningJobs < $this->maxJobs) {
            foreach ($this->jobs as $job) {
                if ($job->status === self::STATUS_QUEUED && $this->runningJobs < $this->maxJobs) {
                    $this->startJob($job);
                }
            }
        }

        $this->httpWorker->tick();
        $active = 0;
        $this->runningJobs = 0;

        foreach ($this->jobs as $job) {
            if ($job->status < self::STATUS_COMPLETED) {
                ++$active;

                if ($job->status === self::STATUS_STARTED) {
                    ++$this->runningJobs;
                }

            } else {
                $this->responseCollector->add($job->response);
                $id = $job->id;
                unset($this->jobs[$id]);
            }
        }

        return $active;
    }

    private function startJob(Job $job): void
    {
        if ($job->status !== self::STATUS_QUEUED) {
            return;
        }

        // start job
        $job->status = self::STATUS_STARTED;
        ++$this->runningJobs;
        $this->httpWorker->download($job, $this->options);
    }

    private function wait(): void
    {
        try {
            while (true) {
                $activeJobs = $this->countActiveJobs();

                if ($activeJobs === 0) {
                    break;
                }
            }
        } catch (\Exception $e) {
             $this->abortJobs();
             throw $e;
        }
    }

    private function abortJobs(): void
    {
        // Job requests
        foreach ($this->jobs as $job) {
            if ($job->status !== self::STATUS_QUEUED) {
                $this->httpWorker->abortRequest($job);
            }
        }

        $this->jobs = [];
        $this->runningJobs = 0;

        // Clear completed job responses
        $this->responseCollector->getAll();
    }

    /**
     * @param array<int, mixed> $options
     * @return array<int, mixed>
     */
    private function formatOptions(array $options): array
    {
        if (!array_key_exists(CURLOPT_USERAGENT, $options)) {
            $options[CURLOPT_USERAGENT] = 'Needs-An-API/1.0';
        }

        return $options;
    }
}
