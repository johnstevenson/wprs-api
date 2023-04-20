<?php declare(strict_types=1);

namespace Wprs\Api\Http;

interface WorkerInterface
{
    public function download(Job $job): void;

    public function tick(): void;

    public function abortRequest(Job $job): void;
}
