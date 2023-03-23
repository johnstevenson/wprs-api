<?php declare(strict_types=1);

namespace Wprs\Api\Http;

class Response
{
    public int $id;
    public string $url;
    public string $contents;

    public function __construct(int $id, string $url, string $contents)
    {
        $this->id = $id;
        $this->url = $url;
        $this->contents = $contents;
    }
}
