<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Filter;

use Wprs\Api\Web\Endpoint\FilterInterface;

class PilotsFilter implements FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array
    {

        $result = [
            'id' => $item['civl_id'],
            'name' => $item['name'],
            'gender' => $item['gender'],
            'points' => $item['points'],
            'rank' => $item['rank'],
        ];

        $xranks = $item['xranks'] ?? null;

        if (!is_array($xranks)) {
            throw new \RuntimeException('xranks property must be an array');
        }

        foreach ($xranks as $xrank) {
            foreach ($xrank as $name => $value) {
                if ($name === 'wo') {
                    $result['rworld'] = $value;
                }

            }
        }

        return $result;
    }

    public function setOptions(array $options): void
    {
    }
}
