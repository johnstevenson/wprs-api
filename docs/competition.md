[Documentation][docs] / Competition endpoint

# Competition endpoint

* [Methods](#methods)
* [Code](#code)
* [Output](#output)
* [Errors](#errors)
* [Parameters](#parameters)
* [Schema](#schema)

This endpoint provides data about a specific competition at a specific ranking period.

## Methods

### _getData()_

```php
getData(string $rankingDate, int $id): array
```

Returns an [output_array][output] of competition data. The [parameters](#parameters) are detailed
below.

### _getBatch()_

```php
getData(string $rankingDate, array $ids): array
```

Return an array of [output_arrays][output] of competition data. The [parameters](#parameters) are
detailed below.

This method is much faster than calling [getData()](#getdata) multiple times.

### _setCurlOptions()_

```php
setCurlOptions(array $options): void
```

See [Curl options][options]

## Code

Gets data about a specific competition at a specific ranking period.

```php
require __DIR__.'/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_COMPETITION;

// set discipline, ranking period and competition id
$discipline = System::DISCIPLINE_PG_XC;
$rankingDate = '2023-03-01';
$compId = 6234;

$endpoint = Factory::createEndpoint($type, $discipline);
$data = $endpoint->getData($rankingDate, $compId);
```

## Output

```jsonc
"meta": {
    "endpoint": "competition",
    "discipline": "paragliding-xc",
    "ranking_date": "2023-03-01",
    "count": 90,
    "version": "1.0"
}
"data": {
    "details": {
        "name": "Bright Open 2023",
        "id": 6321,
        "start_date": "2023-02-11",
        "end_date": "2023-02-18",
        "ta": "1.000",
        "pn": "1.086",
        "pq": "0.409",
        "td": "0.999",
        "tasks": 5,
        "pq_srp": "4050.3",
        "pq_srtp": "15527.4",
        "pilots": 90,
        "pq_rank_date": "",
        "pilots_last_12_months": 11819,
        "comps_last_12_months": 155,
        "days_since_end": 32,
        "last_score": "0.1",
        "winner_score": "43.7",
        "updated": ""
    },
    "items": [
        {
            "rank": 1,
            "pp": "1.000",
            "points": "44.400",
            "td_points": "44.4",
            "score": 3602,
            "pilot": "Gareth Carter",
            "nation_cc": "AUS",
            "civl_id": 17421
        }
        // more pilot result items
    ],
    "errors": [
        // possible errors
    ]
}
```

### _data/details_
Reports the competition parameters and other details.

### _data/items_
Lists the results for each competitor.

### _data/errors_
Lists any [Errors](#errors), or null.

## Errors

An exception will be thrown if the following details values are missing or invalid:

* name
* id
* start_date
* end_date

Some data is not present in the HTML, namely `details/pq_rank_date` and `details/updated`. These
missing date values are NOT reported as errors.

Some competitions may have been unable to run any tasks, and some may be [invalid][comps-invalid].
As a result there may be several missing value errors.

An invalid competition, or one with no tasks, will not have any pilots results so it can easily be
identified if `meta/count` is zero.

## Parameters

### _rankingDate_
A YYYY-MM-DD formatted date string. This parameter is required and cannot be null.

### _id_
The integer id of the competition. This can be obtained from either:

* the `items/id` values in the [Competitions][comps-output] output
* the `items/comps/id` values in the [Pilots][pilots-output] output.

### _ids_
An array of integer competition ids.

## Schema

[Competition JSON Schema](../res/competition-schema.json)

[docs]: 00-intro.md
[options]: 00-intro.md#curl-options
[output]: output.md#output-data
[comps-invalid]: competitions.md#invalid-competitions
[comps-output]: competitions.md#output
[pilots-output]: pilots.md#output
