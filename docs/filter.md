[Documentation][docs] / Filtering output

# Filtering output

* [Filter interface](#filter-interface)
* [Example](#example)

Output data can be filtered by passing a filter instance to the `Factory::createEndpoint` method.

## Filter interface

Your filter must implement the following interface:

```php
namespace Wprs\Api\Web\Endpoint;

interface FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array;
    public function setOptions(array $options): void;
}
```

* The `filter` method can be used to modify each item before it is added to the `data/items` array of
the [output_array][output]. To prevent an item being added, return null.

* The `$errors` parameter is an array of missing value [errors][errors] or null. These will be
included in the [output_array][output] unless modified by the filter method.

* The `setOptions` method receives any non-curl [options][options] that have been set, allowing user
data to be passed data to the filter instance.

## Example

This example and uses a filter to modify the [output][comps-output] from the Competitions endpoint.

### Filter

```php
use Wprs\Api\Web\Endpoint\FilterInterface;

class CompetitionsFilter implements FilterInterface
{
    public function filter(array $item, ?array &$errors): ?array
    {
        // we don't care about errors
        $errors = null;

        // we don't want comps with no tasks
        if ($item['tasks'] === 0 ) {
            return null;
        }

        // we just want these properties
        $result = [
            'start_date' => $item['start_date'],
            'end_date' => $item['end_date'],
            'id' => $item['id'],
            'name' => $item['name'],
            'pilots' => $item['pilots'],
            'updated' => $item['updated'],
        ];

        return $result;
    }

    public function setOptions(array $options): void {}
}
```

### Usage

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_COMPETITIONS;
$discipline = System::DISCIPLINE_PG_XC;

// create filter
$filter = new CompetitionsFilter();

$endpoint = Factory::createEndpoint($type, $discipline, $filter);
$data = $endpoint->getData();
```

[docs]: 00-intro.md
[options]: 00-intro.md#options
[output]: output.md#output-data
[errors]: output.md#errors
[comps-output]: competitions.md#output
