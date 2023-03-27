# wprs-api

* [Usage](#usage)
* [Output data](#output-data)
* [Endpoints](#endpoints)
* [License](#license)

## Usage

**1.** Create an endpoint instance based on the type of data you want and the WPRS discipline:

 ```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Factory;

$type = Rank::ENDPOINT_PILOTS;
$discipline = Rank::DISCIPLINE_PG_XC;

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
All endpoints have a `setOptions` method that accepts an associative array of user options. These
are primarily intended for any additional curl options (identified by the `'curl'` key), but can also
be used for [filtering data](#filtering-data).

```php
$endpoint = Factory::createEndpoint($type, $discipline);

$options['curl'] = [CURLOPT_PROXY => 'https://myproxy.com'];
$endpoint->setOptions($options);
```

#### Filtering data
The `Factory::createEndpoint` method accepts a `FilterInterface` instance as its last parameter.

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

The `setOptions` method will receive any non-curl [user options](#user-options) that have been set.

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

**_getData_**(?string _$rankingDate_, int _$regionId_, ?int _$nationId_ = null,
?int _$scoring_ = null): array


`$regionId` is one of the following constants:
```
Rank::REGION_WORLD
Rank::REGION_EUROPE
Rank::REGION_AFRICA
Rank::REGION_ASIA_OCEANIA
Rank::REGION_PAN_AMERICA
```

`$nationId` is the nation id.

`$scoring` is one of the following constants:
```
Rank::SCORING_OVERALL
Rank::SCORING_FEMALE
Rank::SCORING_JUNIOR
```

#### Example
Gets the ranking of all UK pilots (nation id 223) in Europe for the current ranking period.

 ```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Factory;

$type = Rank::ENDPOINT_PILOTS;

// set discipline and create the endpoint
$discipline = Rank::DISCIPLINE_PG_XC;
$endpoint = Factory::createEndpoint($type, $discipline);

// set region id and other required params
$regionId = Rank::REGION_EUROPE;
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

The current CIVL website only returns 20 pilots per page, so downloading the World ranking data
requires more than 300 requests. This takes some time and can be prone to timeout errors when the
server is busy. This endpoint also provides a `getCount` method, with the same parameters as
`getData`, which downloads a single page and returns the number of pilots in the ranking.

[Back to Endpoints](#endpoints)

### Competition endpoint

**_getData_**(string _$rankingDate_, int _$id_): array

`$id` is the competition id. Note that `$rankingDate` is required and cannot be null.

#### Example

Gets data about a specific competition at a specific ranking period.

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Factory;

$type = Rank::ENDPOINT_COMPETITION;

// set discipline, ranking period and competition id
$discipline = Rank::DISCIPLINE_PG_XC;
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

**_getData_**(?string _$rankingDate = null): array_

This endpoint only needs a `$rankingDate`, which is optional.

#### Example

Gets all competitions used in the current ranking.

```php
require __DIR__.'/app/vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Factory;

$type = Rank::ENDPOINT_COMPETITIONS;

// set discipline
$discipline = Rank::DISCIPLINE_PG_XC;

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
