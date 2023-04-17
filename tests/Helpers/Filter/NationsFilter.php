<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Filter;

use Wprs\Api\Web\Endpoint\FilterInterface;

class NationsFilter implements FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array
    {
        return [
            'id' => $item['nation_id'],
            'name' => $item['nation'],
            'points' => $item['points'],
            'rank' => $item['rank'],
        ];
    }
}
