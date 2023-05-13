# wprs-api

[![packagist](https://img.shields.io/packagist/v/wprs/api)](https://packagist.org/packages/wprs/api)
[![Continuous Integration](https://github.com/johnstevenson/wprs-api/actions/workflows/continuous-integration.yml/badge.svg?branch=main)](https://github.com/johnstevenson/wprs-api/actions?query=branch:main)
![license](https://img.shields.io/github/license/johnstevenson/wprs-api.svg)
![php](https://img.shields.io/packagist/php-v/wprs/api?colorB=8892BF)

A PHP library to scrape data from the FAI CIVL [World Ranking System (WPRS)][wprs]

## Installation

Install the latest version with:

```bash
$ composer require wprs/api
```

## Requirements

* PHP 7.4 minimum, although using the latest PHP version is highly recommended.

## About

The original [WPRS][wprs-original] stopped receiving updates when a new system was introduced in
2021. Despite hopes that a basic API (at minimum) would be provided, this did not happen.

This library provides an API for page-scraping the new web site. It is hoped that this may lead to
a proper API in the future.

## Usage
Full usage information is available in the [documentation][docs].

## License
This library is licensed under the MIT License, see the LICENSE file for details.

[wprs]: https://civlcomps.org/rankings
[wprs-original]: http://civlrankings.fai.org/
[docs]: docs/00-intro.md
