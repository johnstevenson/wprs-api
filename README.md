# wprs-api

* [Usage](#usage)
* [Output data](#output-data)
* [Endpoints](#endpoints)
* [License](#license)

## Usage

**1.** Create an endpoint instance based on the type of data you want and the WPRS discipline:

 ```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\System;
use Wprs\Api\Web\Factory;

$type = System::ENDPOINT_PILOTS;
$discipline = System::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);
```

**2.** Use the `getData` method to return data for a specific ranking period.
```php
$rankingDate = '2023-03-01';

$data = $endpoint->getData($rankingDate, $params);
```

The `$params` value is different for each endpoint and is described in [Endpoints](#endpoints).

### Advanced usage

#### User options
All endpoints have a `setOptions` method that accepts an array of user options. These are primarily
intended for any additional curl options (identified by the `'curl'` key), but can also be used for
[filtering data](#filtering-data).

```php
$endpoint = Factory::createEndpoint($type, $discipline);

/** @var array<string, mixed> */
$options['curl'] = [CURLOPT_PROXY => 'https://myproxy.com'];
$endpoint->setOptions($options);
```

#### Filtering data
The `Factory::createEndpoint` method accepts a `FilterInterface` instance as its 3rd parameter.

```php
namespace Wprs\Api\Web\Endpoint;

interface FilterInterface
{
    public function filter(array $item): ?array;
    public function setOptions(array $options): void;
}
```

The `filter` method can be used to modify each item before it is added to the `data/items` array of
the output data. To prevent an item being added, return null.

The `setOptions` method receives any non-curl [user options](#user-options) that have been set.

## Output data
The data is returned as a PHP array, which can be encoded to JSON. This comprises a `meta` and a
`data` object as shown below.

```jsonc
"meta": {
    "endpoint": "",     // endpoint name
    "discipline": "",   // discipline
    "ranking_date": "", // ranking period
    "count": 0,         // count of objects in data/items
    "version": "1.0"    // api version
},
"data" {
    "details": {
        // endpoint specific details, or null
    },
    "items": [
        // array of endpoint specific objects
    ],
    "errors": [
        // array of value errors, or null
    ]
}
```

Numeric values are returned as JSON numbers if they are integers, or strings if they are
floating-point values. Any missing string values are returned as an empty string.

## Endpoints

* [Pilots](#pilots-endpoint)
* [Competition](#competition-endpoint)
* [Competitions](#competitions-endpoint)

### Pilots endpoint

#### Methods

* **_getData_**(?string _$rankingDate_, int _$regionId_, ?int _$nationId_ = null, ?int _$scoring_ = null): array
* **_getCount_**(?string _$rankingDate_, int _$regionId_, ?int _$nationId_ = null, ?int _$scoring_ = null): int

`$regionId` is one of the following System:: constants:
```
REGION_WORLD, REGION_EUROPE, REGION_AFRICA, REGION_ASIA_OCEANIA, REGION_PAN_AMERICA
```

`$nationId` is the nation id.

`$scoring` is one of the following System:: constants:
```
SCORING_OVERALL, SCORING_FEMALE, SCORING_JUNIOR
```

The `getCount` method downloads a single page and returns the number of pilots in the ranking.

#### Example
Gets the ranking of all UK pilots (nation id 223) in Europe for the current ranking period.

 ```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\System;
use Wprs\Api\Web\Factory;

$type = System::ENDPOINT_PILOTS;

// set discipline and create the endpoint
$discipline = System::DISCIPLINE_PG_XC;
$endpoint = Factory::createEndpoint($type, $discipline);

// set region id and other params, if needed
$regionId = System::REGION_EUROPE;
$nationId = 223;

$data = $endpoint->getData(null, $regionId, $nationId);
```

#### Response

`data/details` is the name and id of the region (and nation, if specified) and the scoring category.
`data/items` lists the details, ranking and competition scores for each pilot.

```jsonc
"meta": {
    "endpoint": "pilots",
    "discipline": "paragliding-xc",
    "ranking_date": "2023-03-01",
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
    ]
}
```

[Back to Endpoints](#endpoints)

### Competition endpoint

#### Methods

* **_getData_**(string _$rankingDate_, int _$id_): array
* **_getBatch_**(string _$rankingDate_, array _$ids_): array

`$rankingDate` is required and cannot be null. `$id` is the competition id. The `getBatch` method
requires an array of integer ids and returns an array of output data arrays.

#### Example

Gets data about a specific competition at a specific ranking period.

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\System;
use Wprs\Api\Web\Factory;

$type = System::ENDPOINT_COMPETITION;

// set discipline, ranking period and competition id
$discipline = System::DISCIPLINE_PG_XC;
$rankingDate = '2023-03-01';
$compId = 6234;

$endpoint = Factory::createEndpoint($type, $discipline);
$data = $endpoint->getData($rankingDate, $compId);
```

#### Response

`data/details` is the competition details. `data/items` lists the results for each competitor.

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
        "pilots_last_12-months": 11819,
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
            "score": "3602",
            "pilot": "Gareth Carter",
            "civil_id": 17421
        }
        // more pilot result items
    ]
}
```
[Back to Endpoints](#endpoints)

### Competitions endpoint

#### Methods

* **_getData_**(?string _$rankingDate = null): array_

This endpoint only needs a `$rankingDate`, which is optional.

#### Example

Gets all competitions used in the current ranking.

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\System;
use Wprs\Api\Web\Factory;

$type = System::ENDPOINT_COMPETITIONS;

// set discipline
$discipline = System::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);
$data = $endpoint->getData();
```

#### Response

`data/details` is empty (null). `data/items` lists the details of each competition.

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
    ]
}
```

[Back to Endpoints](#endpoints)

## License
This library is licensed under the MIT License, see the LICENSE file for details.
