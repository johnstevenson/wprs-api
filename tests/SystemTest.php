<?php declare(strict_types=1);

namespace Wprs\Api\Tests;

use PHPUnit\Framework\TestCase;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Nations\Nations;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\System;

class SystemTest extends TestCase
{
    /**
     * @dataProvider disciplineProvider
     */
    public function testDisciplines(int $discipline, string $expected): void
    {
        $result = System::getDiscipline($discipline);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function disciplineProvider(): array
    {
        return [
            'hg-1'          => [System::DISCIPLINE_HG_CLASS_1, 'hang-gliding-class-1-xc'],
            'hg-1-sport'    => [System::DISCIPLINE_HG_CLASS_1_SPORT, 'hang-gliding-class-1-sport-xc'],
            'hg-2'          => [System::DISCIPLINE_HG_CLASS_2, 'hang-gliding-class-2-xc'],
            'hg-5'          => [System::DISCIPLINE_HG_CLASS_5, 'hang-gliding-class-5-xc'],
            'pg'            => [System::DISCIPLINE_PG_XC, 'paragliding-xc'],
            'accuracy'      => [System::DISCIPLINE_PG_ACCURACY, 'paragliding-accuracy'],
            'acro'          => [System::DISCIPLINE_PG_ACRO_SOLO, 'paragliding-aerobatics'],
            'syncro'        => [System::DISCIPLINE_PG_ACRO_SYNCRO, 'paragliding-acro-syncro'],
        ];
    }

    /**
     * @dataProvider disciplineDisplayProvider
     */
    public function testDisplayDisciplines(int $discipline, string $expected): void
    {
        $result = System::getDisciplineForDisplay($discipline);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function disciplineDisplayProvider(): array
    {
        return [
            'hg-1'          => [System::DISCIPLINE_HG_CLASS_1, 'hang-gliding-class-1'],
            'hg-1-sport'    => [System::DISCIPLINE_HG_CLASS_1_SPORT, 'hang-gliding-class-1-sport'],
            'hg-2'          => [System::DISCIPLINE_HG_CLASS_2, 'hang-gliding-class-2'],
            'hg-5'          => [System::DISCIPLINE_HG_CLASS_5,  'hang-gliding-class-5'],
            'pg'            => [System::DISCIPLINE_PG_XC, 'paragliding-xc'],
            'accuracy'      => [System::DISCIPLINE_PG_ACCURACY, 'paragliding-accuracy'],
            'acro'          => [System::DISCIPLINE_PG_ACRO_SOLO, 'paragliding-acro-solo'],
            'syncro'        => [System::DISCIPLINE_PG_ACRO_SYNCRO, 'paragliding-acro-syncro'],
        ];
    }

    /**
     * @dataProvider endpointProvider
     */
    public function testEndpoints(string $endpoint, string $expected): void
    {
        $result = System::getEndpoint($endpoint);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function endpointProvider(): array
    {
        return [
            'pilots'        => [Pilots::class, 'pilots'],
            'nations'       => [Nations::class, 'nations'],
            'competitions'  => [Competitions::class, 'competitions'],
            'competition'   => [Competition::class, 'competition'],
        ];
    }

    /**
     * @dataProvider regionProvider
     */
    public function testRegions(int $region, string $expected): void
    {
        $result = System::getRegion($region);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function regionProvider(): array
    {
        return [
            'world'         => [System::REGION_WORLD, 'World'],
            'europe'        => [System::REGION_EUROPE, 'Europe'],
            'africa'        => [System::REGION_AFRICA, 'Africa'],
            'asia-oceania'  => [System::REGION_ASIA_OCEANIA, 'Asia-Oceania'],
            'pan-america'   => [System::REGION_PAN_AMERICA, 'Pan America'],
        ];
    }

    /**
     * @dataProvider scoringProvider
     */
    public function testScoring(int $scoring, string $expected): void
    {
        $result = System::getScoring($scoring);
        self::assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function scoringProvider(): array
    {
        return [
            'overall'   => [System::SCORING_OVERALL, 'overall'],
            'female'    => [System::SCORING_FEMALE, 'female'],
            'junior'    => [System::SCORING_JUNIOR, 'junior'],
        ];
    }

    public function testGetRankingDate(): void
    {
        $value = null;
        $result = System::getRankingDate($value);
        self::assertMatchesRegularExpression('/^\\d{4}-\\d{2}-01$/', $result);

        $value = '2023-04-25';
        $expected = '2023-04-01';
        $result = System::getRankingDate($value);
        self::assertEquals($expected, $result);

        $value = 'xxxxx';
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Invalid ranking date');
        System::getRankingDate($value);
    }

    /**
     * @dataProvider paramsProvider
     *
     * @param array<string|int|null> $values
     */
    public function testCheckParams(int $type, array $values, string $message): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage($message);

        System::checkParams($values, $type);

    }

    /**
     * @return array<string, array{0: int, 1: array<mixed>, 2: string}>
     */
    public static function paramsProvider(): array
    {
        return [
            'missing-ids'       => [System::PARAM_ID, [], 'Missing competition ids'],
            'null-id'           => [System::PARAM_ID, [1, null], 'Invalid competition id'],
            'invalid-id'        => [System::PARAM_ID, [1, '2'], 'Invalid competition id'],
            'duplicate-ids'     => [System::PARAM_ID, [1, 1], 'Duplicate competition ids'],
            'missing-dates'     => [System::PARAM_DATE, [], 'Missing ranking dates'],
            'null-date'         => [System::PARAM_DATE, ['2023-03-01', null], 'Invalid ranking date'],
            'invalid-date-1'    => [System::PARAM_DATE, ['2023-03-01', 1], 'Invalid ranking date'],
            'invalid-date-2'    => [System::PARAM_DATE, ['2023-03-01', '2023-03-28'], 'Invalid ranking date'],
            'invalid-date-3'    => [System::PARAM_DATE, ['2023-03-01', 'xxxx'], 'Invalid ranking date'],
            'duplicate-dates'   => [System::PARAM_DATE, ['2023-03-01', '2023-03-01'], 'Duplicate ranking dates'],
        ];
    }
}