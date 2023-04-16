
[Documentation][docs] / Nations endpoint

# Nations endpoint

This endpoint provides the nation rankings for a specific ranking period.

* [Methods](#methods)
* [Code](#code)
* [Output](#output)
* [Errors](#errors)
* [Parameters](#parameters)
* [Schema](#schema)

## Methods

### _getData()_

```php
getData(?string $rankingDate, ?int $regionId): array
```

Returns an [output_array][output] of nation ranking data. The [parameters](#parameters) are detailed
below.

## Code

Gets the nations ranking of all pilots in Europe for the current ranking period.

 ```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_NATIONS;

// set discipline and create the endpoint
$discipline = System::DISCIPLINE_PG_XC;
$endpoint = Factory::createEndpoint($type, $discipline);

// set region id
$regionId = System::REGION_EUROPE;

$data = $endpoint->getData(null, $regionId);
```

## Output

### _data/details_
Reports the name and id of the region, and `count_ww` which is the number of worldwide nations in
this ranking. If the region is World, this will be the same value as `meta/count`.

### _data/items_
Lists the nation ranking data and the scores from the top four pilots.

* The worldwide ranking of each nation is always shown in `items/rank_ww`. If the region is World,
this will be the same value as
`items/rank`.

* The worldwide ranking of each pilot is given in `items/scores/rank`.

### _data/errors_
This is always null.

### _Example_

```jsonc
"meta": {
    "endpoint": "pilots",
    "discipline": "paragliding-xc",
    "ranking_date": "2023-03-01",
    "count": 43,
    "version": "1.0"
}
"data": {
    "details": {
        "region": "Europe",
        "region_id": 1,
        "count_ww": 86,
    },
    "items": [
        {
            "nation": "France",
            "nation_id": 81,
            "rank": 1,
            "rank_ww": 1,
            "pilots": 362,
            "points": "1617.9",
            "scores": [
                {
                    "rank": 1,
                    "points": "421.6",
                    "name": "Honorin Hamard"
                }
                // up to 3 more scores
            ]
        }
        // more nations
    ],
    "errors": null
}
```

## Errors
This endpoint will always throw an exception if a value is missing.

## Parameters

### _rankingDate_
A YYYY-MM-DD formatted date string, or null to use the current ranking period.

### _regionId_
One of the following constants, or null for the default `System::REGION_WORLD`:

```php
    System::REGION_WORLD
    System::REGION_EUROPE
    System::REGION_AFRICA
    System::REGION_ASIA_OCEANIA
    System::REGION_PAN_AMERICA
```

## Schema

[Nations JSON Schema](../res/nations-schema.json)

[docs]: 00-intro.md
[output]: output.md#output-data
