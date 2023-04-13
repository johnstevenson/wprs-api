# WPRS API Documentation

* [Overview](#overview)
* [Output](#output)
* [Options](#options)
* [Filtering output](#filtering-output)
* [Error handling](#error-handling)

## Overview

This library is based around the concept of endpoints. An endpoint is one of the various WPRS
rankings (ladders) and the following chapters include full usage documentation and examples for each
type.

* [Pilots endpoint](pilots.md)
* [Competition endpoint](competition.md)
* [Competitions endpoint](competitions.md)

This section shows how to get ranking data using an endpoint instance, in two simple steps.

**1.** Create an endpoint instance based on the type of data you want and the WPRS discipline:

 ```php
require __DIR__.'/app/vendor/autoload.php';

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

## Output
The data returned is a PHP array. Its basic structure is defined in [Output data](output.md),
with more specific details given for each endpoint:

* [Competition output](competition.md#output)
* [Competitions output](competitions.md#output)
* [Pilots output](pilots.md#output)


## Options

All endpoints have a `setOptions` method that accepts an array of user options. These are primarily
intended for additional curl options that might be needed, identified by the `'curl'` key:

```php
$endpoint = Factory::createEndpoint($type, $discipline);

/** @var array<string, mixed> */
$options['curl'] = [CURLOPT_PROXY => 'https://myproxy.com'];
$endpoint->setOptions($options);
```

Any non-curl options can used when filtering output data.

## Filtering output

The library allows you to [filter](filter.md) the output data.

## Error handling

The library will throw an exception if something goes wrong, for example if a download operation
fails, an HTML element is missing or something unexpected happens.

Although it is not shown in the example code, best practice is to wrap all endpoint methods in a
try...catch statement. `System::getExceptionMessage()` can be used to get an informative message
from a caught exception.

```php
try {
    $data = $endpoint->getData('2023-03-01', ...$params);
} catch (\Exception $e) {
    error = System::getExceptionMessage($e);
}
```