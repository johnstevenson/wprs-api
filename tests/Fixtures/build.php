<?php

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Tests\Helpers\Builder\HtmlBuilder;

/**
 * A script to build test fixtures from current data
 */

$builder = new HtmlBuilder();

$builder->build();

