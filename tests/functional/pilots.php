<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Ranking;
use Wprs\Api\Web\Factory;

$type = Ranking::ENDPOINT_PILOTS;
$activity = Ranking::ACTIVITY_PG_XC;

$endpoint = Factory::createEndpoint($type, $activity);
$endpoint->setRestricted();

$params = Factory::createParams($type, 0);

try {
    $start = microtime(true);
    $count = $endpoint->getOverallCount(null, $params);
} catch (Exception $e) {
    printf(
        'Failed in %s, line %d, message: %s%s',
        $e->getFile(),
        $e->getLine(),
        $e->getMessage(),
        PHP_EOL
    );
    exit(1);
}

printf('Pilots: %d%s', $count, PHP_EOL);
exit(0);
