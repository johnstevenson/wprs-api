# WPRS API Documentation

* [Overview](#overview)
* [Output](#output)
* [Disciplines](#disciplines)
* [Curl options](#curl-options)
* [Filtering output](#filtering-output)
* [Error handling](#error-handling)

## Overview

This library is based around the concept of endpoints. An endpoint is one of the various WPRS
rankings (ladders) and the following chapters show usage information and examples for each type.

* [Pilots endpoint](pilots.md)
* [Nations endpoint](nations.md)
* [Competitions endpoint](competitions.md)
* [Competition endpoint](competition.md)

This section shows how to get ranking data, in two simple steps.

**1.** Create an endpoint instance based on the type of data you want and the WPRS discipline:

 ```php
require __DIR__.'/vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_PILOTS;
$discipline = System::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);
```

**2.** Use the `getData` method to return data for a specific ranking period.
```php
$data = $endpoint->getData('2023-03-01', ...$params);
```

## Disciplines

The following System constants are provided:

```php
    System::DISCIPLINE_HG_CLASS_1
    System::DISCIPLINE_HG_CLASS_1_SPORT
    System::DISCIPLINE_HG_CLASS_2
    System::DISCIPLINE_HG_CLASS_5

    System::DISCIPLINE_PG_XC
    System::DISCIPLINE_PG_ACCURACY
    System::DISCIPLINE_PG_ACRO_SOLO
    System::DISCIPLINE_PG_ACRO_SYNCRO
```

## Output
The data returned is a PHP array. Its basic structure is defined in [Output data](output.md),
with more specific details given for each endpoint:

* [Pilots output](pilots.md#output)
* [Nations output](nations.md#output)
* [Competitions output](competitions.md#output)
* [Competition output](competition.md#output)

## Curl options

The library uses `curl` under the hood, coupled with `composer/ca-bundle` for cross-platform
certificate location. If this doesn't work for your configuration, you can supply additional
options using the `setCurlOptions()` method which is available on all endpoints.

This method takes an array of options that will be passed to PHP's _curl_setopt_array()_.

### User-Agent
The library sets this request header to `Needs-An-API/1.0`. To change it to something else:

```php
$endpoint = Factory::createEndpoint($type, $discipline);

$options[CURLOPT_USERAGENT] = 'My-User-Agent/1.0';
$endpoint->setCurlOptions($options);
```

## Filtering output

The library allows you to [filter](filter.md) the output data, so you only get the values you need.

## Error handling

The library will throw an exception if something goes wrong, for example if a download operation
fails, an HTML element is missing or something unexpected happens.

Although not shown in the example code, best practice is to wrap all endpoint methods in a
try...catch statement. `System::getExceptionMessage()` can be used to get an informative message
from a caught exception.

```php
try {
    $data = $endpoint->getData(...$params);
} catch (\Exception $e) {
    $error = System::getExceptionMessage($e);
}
```
