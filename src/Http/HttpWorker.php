<?php declare(strict_types=1);

namespace Wprs\Api\Http;

use Composer\CaBundle\CaBundle;

class HttpWorker
{
    /** @var array<Job> */
    private array $jobs = [];
    private int $maxRetries = 3;

    /** @var array<int, string> */
    private array $caOptions;

    /** @var \CurlMultiHandle */
    private $multiHandle;

     /** @var \CurlShareHandle */
    private $shareHandle;
    private bool $gzip;

    public function __construct()
    {
        $caBundle = CaBundle::getSystemCaRootBundlePath();
        $key = is_dir($caBundle) ? CURLOPT_CAPATH : CURLOPT_CAINFO;
        $this->caOptions = [$key => $caBundle];

        $this->multiHandle = $mh = curl_multi_init();

        curl_multi_setopt($mh, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX );
        curl_multi_setopt($mh, CURLMOPT_MAX_HOST_CONNECTIONS, 8);

        $this->shareHandle = $sh = curl_share_init();
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
        curl_share_setopt($sh, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);

        $version = curl_version();
        $this->gzip = (bool) ($version['libz_version'] ?? null);
    }

    /**
     * @param array<int, mixed> $options
     */
    public function download(Job $job, array $options): void
    {
        $ch = curl_init($job->url);

        if ($ch === false) {
            throw new \RuntimeException('curl_init failed with: '.$job->url);
        }

        $job->curlHandle = $ch;
        $job->curlId = $id = (int) $ch;

        // Create body file handle
        $job->bodyHandle = $this->openBodyHandle($job);
        $this->jobs[$id] = $job;

        // curl options - CA and timeout options first
        curl_setopt_array($ch, $this->caOptions);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        // User curl options next
        if (count($options) !== 0) {
            curl_setopt_array($ch, $options);
        }

        curl_setopt($ch, CURLOPT_SHARE, $this->shareHandle);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FILE, $job->bodyHandle);

        curl_setopt($ch, CURLOPT_ENCODING, $this->gzip ? 'gzip' : '');
        curl_setopt($ch, CURLOPT_HTTP_CONTENT_DECODING, $this->gzip ? 0 : 1);

        $this->checkMultiCode(curl_multi_add_handle($this->multiHandle, $ch));
    }

    public function tick(): void
    {
        if (count($this->jobs) === 0) {
            return;
        }

        $active = true;
        $this->checkMultiCode(curl_multi_exec($this->multiHandle, $active));
        $this->checkMultiSelect(curl_multi_select($this->multiHandle));

        while (false !== ($info = curl_multi_info_read($this->multiHandle))) {
            if (CURLMSG_DONE !== $info['msg']) {
                continue;
            }

            $curlHandle = $info['handle'];

            /** @var Job|null */
            $job = $this->jobs[(int) $curlHandle] ?? null;
            if ($job === null) {
                continue;
            }

            $statusCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            $errno = curl_errno($curlHandle);
            $error = curl_error($curlHandle);

            $this->removeCurlHandle($job);
            $this->removeFromJobs($job);

            try {

                if (CURLE_OK !== $errno) {
                    if ($this->retryFromCurlError($job, $errno, $error)) {
                        continue;
                    }

                    $msg = sprintf('curl error %d: %s (%s)', $errno, $error, $job->url);
                    throw new \RuntimeException($msg);
                }

                rewind($job->bodyHandle);
                $contents = (string) stream_get_contents($job->bodyHandle);

                $this->closeBodyHandle($job);

                if ($statusCode !== 200) {
                    if ($this->retryFromStatusError($job, $statusCode)) {
                        continue;
                    }

                    $msg = sprintf('http error %d downloading %s', $statusCode, $job->url);
                    throw new \RuntimeException($msg);
                }

                $job->status = HttpDownloader::STATUS_COMPLETED;
                $job->response = new Response($job->id, $job->url, $contents);

            } catch (\Exception $e) {
                $job->status = HttpDownloader::STATUS_FAILED;
                throw $e;
            }
        }
    }

    public function abortRequest(Job $job): void
    {
        $this->removeCurlHandle($job);
        $this->closeBodyHandle($job);
        $this->removeFromJobs($job);
    }

    private function checkMultiCode(int $code): void
    {
        if (CURLM_OK !== $code) {
            $msg = sprintf('curl multi error (%d): %s', $code, curl_multi_strerror($code));
            throw new \RuntimeException($msg);
        }
    }

    private function checkMultiSelect(int $fds): void
    {
        if (-1 === $fds) {
            $code = curl_multi_errno($this->multiHandle);
            $msg = sprintf('curl multi select error (%d): %s', $code, curl_multi_strerror($code));
            throw new \RuntimeException($msg);
        }
    }

    private function closeBodyHandle(Job $job): void
    {
        /* @phpstan-ignore-next-line */
        if (is_resource($job->bodyHandle)) {
            fclose($job->bodyHandle);
        }
    }

    /**
     * @return Resource
     */
    private function openBodyHandle(Job $job)
    {
        if (false === ($handle = @fopen('php://temp/maxmemory:524288', 'w+b'))) {
            throw new \RuntimeException('Failed to open body stream');
        }

        return $handle;
    }

    private function removeCurlHandle(Job $job): void
    {
        if ($job->curlHandle !== null) {
            curl_multi_remove_handle($this->multiHandle, $job->curlHandle);
            curl_close($job->curlHandle);
            $job->curlHandle = null;
        }
    }

    private function removeFromJobs(Job $job): void
    {
        if (isset($this->jobs[$job->curlId])) {
            unset($this->jobs[$job->curlId]);
        }
    }

    private function restartJobWithDelay(Job $job): void
    {
        ++$job->retries;

        if ($job->retries >= 3) {
            usleep(500000); // half a second delay for 3rd retry and beyond
        } elseif ($job->retries >= 2) {
            usleep(100000); // 100ms delay for 2nd retry
        } // no sleep for the first retry

        $this->closeBodyHandle($job);
        $this->download($job, $job->options);
    }

    private function retryFromCurlError(Job $job, int $errno, string $error): bool
    {
        if ($job->retries >= $this->maxRetries) {
            return false;
        }

        // CURLE_COULDNT_RESOLVE_HOST = 6, CURLE_COULDNT_CONNECT = 7
        // CURLE_HTTP2 = 16, CURLE_HTTP2_STREAM = 92
        $allowed = in_array($errno, [6, 7, 16, 92], true);

        // CURLE_SSL_CONNECT_ERROR = 35
        $reset = ($errno === 35 && false !== strpos($error, 'Connection reset by peer'));

        if ($result = ($allowed || $reset)) {
            $this->restartJobWithDelay($job);
        }

        return $result;
    }

    private function retryFromStatusError(Job $job, int $statusCode): bool
    {
        if ($job->retries >= $this->maxRetries) {
            return false;
        }

        $allowed = [500, 502, 503, 504];

        if ($result = in_array($statusCode, $allowed, true)) {
            $this->restartJobWithDelay($job);
        }

        return $result;
    }
}
