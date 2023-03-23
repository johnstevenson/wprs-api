<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Ranking;
use Wprs\Api\Web\Factory;

$type = Ranking::ENDPOINT_COMPETITIONS;
$activity = Ranking::ACTIVITY_PG_XC;

$endpoint = Factory::createEndpoint($type, $activity);

try {
    $data = $endpoint->getData(null);
} catch (Exception $e) {
    failWithMessage($e);
}

$count = $data['meta']['count'];
$rankingDate = $data['meta']['ranking_date'];
$lastDate = '0000-00-00';
$compId = 0;
$name = '';

foreach ($data['data']['items'] as $item) {
    $date = $item['updated'];

    if ($date > $lastDate) {
        $lastDate = $date;
        $compId = $item['id'];
        $name = $item['name'];
    }
}

printf('Competitions: %d, last update: %s, (%s)%s', $count, $lastDate, $name, PHP_EOL);

$type = Ranking::ENDPOINT_COMPETITION;
$endpoint = Factory::createEndpoint($type, $activity);

try {
    $data = $endpoint->getData($rankingDate, $compId);
} catch (Exception $e) {
    failWithMessage($e);
}

$count = $data['meta']['count'];
$name = $data['data']['details']['name'];

printf('Competition id: %d, name: %s, pilots: %d%s', $compId, $name, $count, PHP_EOL);

exit(0);

function failWithMessage(Exception $e)
{
    printf(
        'Failed in %s, line %d, message: %s%s',
        $e->getFile(),
        $e->getLine(),
        $e->getMessage(),
        PHP_EOL
    );
    exit(1);
}
