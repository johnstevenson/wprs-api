<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Factory;
use Wprs\Api\Web\Rank;

$type = Rank::ENDPOINT_COMPETITIONS;
$discipline = Rank::DISCIPLINE_PG_XC;

$endpoint = Factory::createEndpoint($type, $discipline);

try {
    $data = $endpoint->getData(null);
} catch (Exception $e) {
    echo Rank::getExceptionMessage($e);
    exit(1);
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

// @phpstan-ignore-next-line
printf('Competitions: %d, last update: %s, (%s)%s', $count, $lastDate, $name, PHP_EOL);

$type = Rank::ENDPOINT_COMPETITION;
$endpoint = Factory::createEndpoint($type, $discipline);

try {
    // @phpstan-ignore-next-line
    $data = $endpoint->getData($rankingDate, $compId);
} catch (Exception $e) {
    echo Rank::getExceptionMessage($e);
    exit(1);
}

$count = $data['meta']['count'];
$name = $data['data']['details']['name'] ?? '';

// @phpstan-ignore-next-line
printf('Competition id: %d, name: %s, pilots: %d%s', $compId, $name, $count, PHP_EOL);

$errors = $data['data']['errors'] ?? null;
if ($errors !== null) {
    printf('Errors: %s%s%s', PHP_EOL, implode(PHP_EOL, $errors), PHP_EOL);
}


exit(0);
