<?php

namespace Wprs\Api\Http;

class ResponseCollector
{
    /** @var array<Response> */
    private $items = [];

    public function add(Response $response)
    {
        $this->items[$response->id] = $response;
    }

    public function getAll()
    {
        $result = $this->items;
        $this->items = [];

        return $result;
    }
}
