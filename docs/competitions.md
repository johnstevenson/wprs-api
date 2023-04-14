[Documentation][docs] / Competitions endpoint

# Competitions endpoint

* [Methods](#methods)
* [Example](#example)
* [Output](#output)
* [Errors](#errors)
* [Schema](#schema)

This endpoint provides a list of all competitions used in a specific ranking period.

## Methods
### getData()

```php
getData(?string $rankingDate = null): array
```

Returns an [output_array][output] of competitions used in the ranking period.

The `$rankingDate` parameter is optional and the current ranking date will be used if it is not
provided. Otherwise it must be a YYYY-MM-DD formatted date string.

## Example
Gets all competitions used in the current ranking.

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_COMPETITIONS;

// set discipline
$discipline = System::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);
$data = $endpoint->getData();
```

## Output

_**data/details**_ is always null.

_**data/items**_ lists the parameters and details of each competition.

_**data/errors**_ lists any [Errors](#errors), or null.

```jsonc
"meta": {
    "endpoint": "competitions",
    "discipline": "paragliding-xc",
    "ranking_date": "2023-03-01",
    "count": 354,
    "version": "1.0"
},
"data": {
    "details": null,
    "items": [
        {
            "name": "Bright Open 2023",
            "id": 6321,
            "start_date": "2023-02-11",
            "end_date": "2023-02-18",
            "ta": "1.000",
            "pn": "1.086",
            "pq": "0.409",
            "td": "0.999",
            "tasks": 5,
            "pilots": 90,
            "pilots_last_12_months": 11819,
            "comps_last_12_months": 155,
            "days_since_end": 32,
            "last_score": "0.1",
            "winner_score": "43.7",
            "updated": "2023-02-24"
        }
        // more competition items
    ],
    "errors": [
        // possible errors
    ]
}
```

## Errors

An exception will be thrown if the following items values are missing or invalid:

* name
* id
* start_date
* end_date

The `items/updated` value is not always present in the HTML before mid-2022. This missing date value
is NOT reported as an error.

### Invalid competitions
Unfortunately, invalid competitions are included in the HTML output. These may be "test" events
that were used when updating the system, or events that should not be listed (wrong discipline for
example).

Events with no tasks have always been listed and were easy to identify from the HMTL. But data from
the new HTML can be inconsistent and the inclusion of invalid competitions has made this harder.
As a result there may be several missing value errors.

[Missing values][missing] are given a default value, so an invalid competition, or one with no
tasks, can be identified if `items/tasks` is zero.

## Schema

[Competitions JSON Schema](../res/competitions-schema.json)

[docs]: 00-intro.md
[output]: output.md#output-data
[missing]: output.md#missing-values
