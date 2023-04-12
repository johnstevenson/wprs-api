<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\System;

$type = System::ENDPOINT_PILOTS;
$discipline = System::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);

try {
    $count = $endpoint->getCount(null, System::REGION_WORLD);
} catch (Exception $e) {
    echo System::getExceptionMessage($e);
    exit(1);
}

printf('Pilots: %d%s', $count, PHP_EOL);
exit(0);
