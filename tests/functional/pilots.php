<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\Rank;

$type = Rank::ENDPOINT_PILOTS;
$discipline = Rank::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);
$endpoint->setRestricted();

try {
    $start = microtime(true);
    $count = $endpoint->getCount(null, Rank::REGION_WORLD);
} catch (Exception $e) {
    echo Rank::getExceptionMessage($e);
    exit(1);
}

printf('Pilots: %d%s', $count, PHP_EOL);
exit(0);
