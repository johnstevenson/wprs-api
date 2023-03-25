<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;

$endpoint = new Pilots(Rank::DISCIPLINE_PG_XC);
$endpoint->setRestricted();

try {
    $start = microtime(true);
    $count = $endpoint->getCount(null, Rank::REGION_WORLD);
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
