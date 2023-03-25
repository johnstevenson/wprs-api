<?php

namespace Wprs\Api\Http;

class ResponseCollector
{
    /** @var array<Response> */
    private $items = [];

    public function add(Response $response): void
    {
        $this->items[$response->id] = $response;
    }

    /**
     * @return array<Response>
     */
    public function getAll(): array
    {
        $result = $this->items;
        $this->items = [];

        return $result;
    }
}
