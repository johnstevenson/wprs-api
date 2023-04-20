<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Filter;

use Wprs\Api\Web\Endpoint\FilterInterface;

class CompetitionsFilter implements FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array
    {
        // we don't want errors
        $errors = null;

        // we don't want competitions with no tasks
        $keys = ['pn', 'td', 'tasks', 'pilots'];

        foreach ($keys as $key) {
            if ($item[$key] == 0) {
                return null;
            }
        }

        // we just want these properties
        $result = [
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date'],
            'id' => $item['id'],
            'name' => $item['name'],
            'tasks' => $item['tasks'],
            'pilots' => $item['pilots'],
            'updated' => $item['updated'],
        ];

        return $result;
    }
}
