<?php declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use Wprs\Api\Web\Rank;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;

function showError(Exception $e): void
{
    $format = 'Failed in %s, line %d, message: %s%s';
    printf($format, $e->getFile(), $e->getLine(), $e->getMessage(), PHP_EOL);
}

$discipline = Rank::DISCIPLINE_PG_XC;
$endpoint = new Competitions($discipline);

try {
    $data = $endpoint->getData(null);
} catch (Exception $e) {
    showError($e);
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

$endpoint = new Competition($discipline);

try {
    // @phpstan-ignore-next-line
    $data = $endpoint->getData($rankingDate, $compId);
} catch (Exception $e) {
    showError($e);
    exit(1);
}

$count = $data['meta']['count'];
$name = $data['data']['details']['name'] ?? '';

// @phpstan-ignore-next-line
printf('Competition id: %d, name: %s, pilots: %d%s', $compId, $name, $count, PHP_EOL);

exit(0);
