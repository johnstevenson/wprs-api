[Documentation][docs] / Pilots endpoint

# Pilots endpoint

* [Methods](#methods)
* [Code](#code)
* [Output](#output)
* [Errors](#errors)
* [Parameters](#parameters)
* [Schema](#schema)

This endpoint provides the main pilot rankings for a specific ranking period.

## Methods

### _getData()_

```php
getData(
    ?string $rankingDate,
    ?int $regionId,
    ?int $nationId = null,
    ?int $scoring = null
): array
```

Returns an [output_array][output] of pilot ranking data. The [parameters](#parameters) are detailed
below.

#### Warning
The website only lists 20 pilots per page, so downloading the HTML will involve multiple requests.
The worst case is fetching the World data for `System::DISCIPLINE_PG_XC` which requires more than
300 requests.

### _getBatch()_

```php
getBatch(
    array $rankingDates,
    ?int $regionId,
    ?int $nationId = null,
    ?int $scoring = null
): array
```

Returns an array of [output_arrays][output] of pilot ranking data for the specified ranking periods.
The [parameters](#parameters) are detailed below.

This method is much faster than calling [getData()](#getdata) multiple times, but please see the
[warning](#warning);

### _getCount()_

```php
getCount(
    ?string $rankingDate,
    ?int $regionId,
    ?int $nationId = null,
    ?int $scoring = null
): int
```

Returns the number of pilots in the ranking by downloading a single page. The
[parameters](#parameters) are detailed below.

### _setCurlOptions()_

```php
setCurlOptions(array $options): void
```

See [Curl options][options]

## Code

Gets the ranking of all UK pilots (nation id 223) in Europe for the current ranking period.

 ```php
require __DIR__.'/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_PILOTS;

// set discipline and create the endpoint
$discipline = System::DISCIPLINE_PG_XC;
$endpoint = Factory::createEndpoint($type, $discipline);

// set region id and other params, if needed
$regionId = System::REGION_EUROPE;
$nationId = 223;

$data = $endpoint->getData(null, $regionId, $nationId);
```

## Output

```jsonc
"meta": {
    "endpoint": "pilots",
    "discipline": "paragliding-xc",
    "ranking_date": "2023-03-01",
    "updated": "2023-03-01T04:05:00Z",
    "count": 190,
    "version": "1.0"
}
"data": {
    "details": {
        "region": "Europe",
        "region_id": 1,
        "nation": "United Kingdom",
        "nation_id": 233,
        "scoring": "overall",
    },
    "items": [
        {
            "civl_id": 10389,
            "name": "Juan Sebastian Ospina Restrepo",
            "gender": "Male",
            "nation": "United Kingdom",
            "nation_id": 233,
            "rank": 1,
            "xranks": [
                { "co": "15" },
                { "wo": "15" }
            ],
            "points": "355.9",
            "comps": [
                {
                    "rank": 6,
                    "points": "105.0",
                    "name": "12th Paragliding World Cup Superfinal, Mexico - Valle de Bravo",
                    "id": 6072
                }
                // up to 3 more comps
            ]
        }
        // more pilots
    ],
    "errors": null
}
```

### _data/details_
Reports the name and id of the region and nation, and the scoring category. If a _nation_id_ is not
used, the nation name will be an empty string and its id will be 0.

### _data/items_
Lists the ranking data and competition scores for each pilot.

### _data/errors_
This is always null.

### _xranks_
This property, at `items/xranks`, is an array of the pilot's main rankings in relation to the
criteria being used.

When world region and overall scoring is used, this array will be empty because there are no other
rankings to show.

But if a specific region or a specific scoring category is used, then this array will contain the
pilot's world ranking `wo`. If a nation id is used as well, then the pilot's continent ranking `co`
will be included.

The table below shows all _xranks_ properties.

| Property | Description       |
|----------|-------------------|
| wo       | world overall     |
| wf       | world female      |
| wj       | world junior      |
| co       | continent overall |
| cf       | continent female  |
| cj       | continent junior  |
| no       | nation overall    |

## Errors
This endpoint will always throw an exception if a value is missing.

## Parameters

### _rankingDate_
A YYYY-MM-DD formatted date string, or null to use the current ranking period.

### _rankingDates_
An array of YYYY-MM-01 formatted date strings.

### _regionId_
One of the following constants, or null for the default `System::REGION_WORLD`:

```php
    System::REGION_WORLD
    System::REGION_EUROPE
    System::REGION_AFRICA
    System::REGION_ASIA_OCEANIA
    System::REGION_PAN_AMERICA
```

### _nation_id_
The nation id or null. There is currently no easy way to obtain a list of nations ids, other than to
use the Nations endpoint.

### _scoring_
One of the following constants, or null for the default `System::SCORING_OVERALL`:

```php
    System::SCORING_OVERALL
    System::SCORING_FEMALE
    System::SCORING_JUNIOR
```

## Schema

[Pilots JSON Schema](../res/pilots-schema.json)

[docs]: 00-intro.md
[options]: 00-intro.md#curl-options
[output]: output.md#output-data
