<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Filter;

use Wprs\Api\Web\Endpoint\FilterInterface;

class CompetitionFilter implements FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array
    {
        $errors = null;

        $result = [
            'civl_id' => $item['civl_id'],
            'name' => $item['pilot'],
            'points' => $item['points'],
        ];

        return $result;
    }

    public function setOptions(array $options): void
    {
    }
}
