[Documentation][docs] / Filtering output

# Filtering output

* [Filter interface](#filter-interface)
* [Example](#example)

Output data can be filtered by passing a _filter_ instance to the `Factory::createEndpoint` method.

## Filter interface

Your _filter_ must implement the following interface:

```php
namespace Wprs\Api\Web\Endpoint;

interface FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array;
}
```

* The `filter` method can be used to modify each item before it is added to `data/items` in the
[output_array][output]. To prevent an item being added, return null.

* The `$errors` parameter is an array of missing value [errors][errors], or null. These errors will
be included in the [output_array][output] unless modified by the filter method. To prevent errors
being included, set the variable to null.

## Example

This example and uses a filter to modify the [output][comps-output] from the Competitions endpoint.

### Filter

```php
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
```

### Usage

```php
require __DIR__.'/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_COMPETITIONS;
$discipline = System::DISCIPLINE_PG_XC;

// create the filter
$filter = new CompetitionsFilter();

$endpoint = Factory::createEndpoint($type, $discipline, $filter);
$data = $endpoint->getData();
```

[docs]: 00-intro.md
[options]: 00-intro.md#options
[output]: output.md#output-data
[errors]: output.md#errors
[comps-output]: competitions.md#output
