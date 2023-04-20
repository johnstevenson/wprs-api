# WPRS API Documentation

* [Overview](#overview)
* [Output](#output)
* [Disciplines](#disciplines)
* [Curl options](#curl-options)
* [Filtering output](#filtering-output)
* [Error handling](#error-handling)

## Overview

This library is based around endpoints, which mainly represent the various WPRS rankings
(ladders). The following chapters give usage information and examples for each type.

* [Pilots endpoint](pilots.md)
* [Nations endpoint](nations.md)
* [Competitions endpoint](competitions.md)
* [Competition endpoint](competition.md)

Meanwhile, this section outlines the general process of getting ranking data, in two simple steps.

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

You can get JSON output by passing the data to `System::toJson()`:

```php
$data = $endpoint->getData(...$params);

// get prettified JSON
$json = System::toJson($data, true);
```

## Curl options

The library uses _curl_ under the hood, coupled with `composer/ca-bundle` for cross-platform
certificate discovery. If this doesn't work with your setup, you can supply additional _curl_
options using the `setCurlOptions()` method which is available on all endpoints.

This method takes an array of options which will be passed internally to the PHP function
[curl_setopt_array()][curlsetopts].

### User-Agent
This request header is set to `Needs-An-API/1.0 (packagist.org; wprs/api)`, but you can change it to
something else:

```php
$endpoint = Factory::createEndpoint($type, $discipline);

$options[CURLOPT_USERAGENT] = 'My-User-Agent/1.0';
$endpoint->setCurlOptions($options);
```

## Filtering output

The library allows you to [filter](filter.md) the output data, so that you only get the values you
need.

## Error handling

The library will throw an exception if something goes wrong, for example if a download operation
fails, an HTML element is missing or something unexpected happens.

Although not shown in the code examples, best practice is to wrap all endpoint methods in a
try...catch statement. `System::getExceptionMessage()` can be used to get an informative message
from a caught exception.

```php
try {
    $data = $endpoint->getData(...$params);
} catch (\Exception $e) {
    $error = System::getExceptionMessage($e);
}
```

[curlsetopts]: https://www.php.net/manual/en/function.curl-setopt-array.php
