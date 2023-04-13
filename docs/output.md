[Documentation][docs] / Output data

# Output data

The output data returned from all endpoints is a PHP array. It can be encoded to JSON and we use
this representation to illustrate it.

The output data is referred to as an `output_array` throughout this documentation.

The data is an object comprising [meta](#meta) and [data](#data) root properties:


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
        // endpoint-specific details, or null
    },
    "items": [
        // array of endpoint-specific objects
    ],
    "errors": [
        // array of key names of missing values, or null
    ]
}
```

JSON schemas are provided for all endpoints and can be found in the `res` directory.

## Data types
In addition to container elements (objects and arrays), data types are restricted to string and
integer values.

* Floating points values are stored as strings, so as to have a fixed-point
representation.
* Date values are stored as formatted `YYYY-MM-DD` strings.

### Missing values
Unfortunately, not all of the expected data can be found in the HTML and this inconsistency can
result in missing values. In cases where a missing value has not thrown an exception, it will be
reported in the [errors](#errors) property and given one of the following values:

* missing string values will be an empty string `""`
* missing integer values will be `0`
* missing floating point values will be `"0.0"`

## meta
This object has the same properties for all endpoints.

* _endpoint_ `string`
    * pilots
    * competition
    * competitions
    * nations

* _discipline_ `string`
    * hang-gliding-class-1-xc
    * hang-gliding-class-1-sport-xc
    * hang-gliding-class-2-xc
    * hang-gliding-class-5-xc
    * paragliding-xc
    * paragliding-accuracy
    * paragliding-acro-solo
    * paragliding-acro-syncro

* _ranking_date_ `string`
    * formatted YYYY-MM-DD

* _count_ `integer`
    * number of items in `data/items`

* _version_ `string`
    * currently 1.0

## data
This object has the same three properties for all endpoints, namely the [details](#details),
[items](#items) and [errors](#errors) containers.

### _details_
This is either an object containing endpoint-specific properties, or null.

### _items_
This is always an array of endpoint-specific objects.

### _errors_
This is either an array of string values representing the property names of missing values, or null.

* missing [details](#details) values are formatted `details/key`, where _key_ is the property name of
the value.

* missing [items](#items) values are formatted `items/index/key` where _index_ is the index of the
item and _key_ is the property name of the value.

[docs]: 00-intro.md
