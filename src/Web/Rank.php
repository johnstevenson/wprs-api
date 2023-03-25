<?php declare(strict_types=1);

namespace Wprs\Api\Web;

class Rank
{
    public const URL_RANKING = 'https://civlcomps.org/ranking';

    public const DISCIPLINE_HG_CLASS_1 = 2;
    public const DISCIPLINE_HG_CLASS_1_SPORT = 10;
    public const DISCIPLINE_HG_CLASS_2 = 8;
    public const DISCIPLINE_HG_CLASS_5 = 3;

    public const DISCIPLINE_PG_XC = 1;
    public const DISCIPLINE_PG_ACCURACY = 4;
    public const DISCIPLINE_PG_ACRO_SOLO = 5;
    public const DISCIPLINE_PG_ACRO_SYNCRO = 6;

    private const DISCIPLINES = [
        self::DISCIPLINE_HG_CLASS_1 => 'hang-gliding-class-1-xc',
        self::DISCIPLINE_HG_CLASS_1_SPORT => 'hang-gliding-class-1-sport-xc',
        self::DISCIPLINE_HG_CLASS_2 => 'hang-gliding-class-2-xc',
        self::DISCIPLINE_HG_CLASS_5 => 'hang-gliding-class-5-xc',

        self::DISCIPLINE_PG_XC => 'paragliding-xc',
        self::DISCIPLINE_PG_ACCURACY => 'paragliding-accuracy',
        self::DISCIPLINE_PG_ACRO_SOLO => 'paragliding-aerobatics',
        self::DISCIPLINE_PG_ACRO_SYNCRO => 'paragliding-acro-syncro',
    ];

    public const ENDPOINT_PILOT = 101;
    public const ENDPOINT_PILOTS = 102;
    public const ENDPOINT_NATIONS = 103;
    public const ENDPOINT_COMPETITION = 104;
    public const ENDPOINT_COMPETITIONS = 105;

    private const ENDPOINTS = [
        self::ENDPOINT_PILOT => 'pilot',
        self::ENDPOINT_PILOTS => 'pilots',
        self::ENDPOINT_NATIONS => 'nations',
        self::ENDPOINT_COMPETITION => 'competition',
        self::ENDPOINT_COMPETITIONS => 'competitions',
    ];

    public const REGION_WORLD = 0;
    public const REGION_EUROPE = 1;
    public const REGION_AFRICA = 2;
    public const REGION_ASIA_OCEANIA = 3;
    public const REGION_PAN_AMERICA = 4;

    private const REGIONS = [
        self::REGION_WORLD => 'World',
        self::REGION_EUROPE => 'Europe',
        self::REGION_AFRICA => 'Africa',
        self::REGION_ASIA_OCEANIA => 'Asia-Oceania',
        self::REGION_PAN_AMERICA => 'Pan America',
    ];

    public const SCORING_OVERALL = 0;
    public const SCORING_FEMALE = 1;
    public const SCORING_JUNIOR = 2;

    private const SCORING = [
        self::SCORING_OVERALL => 'overall',
        self::SCORING_FEMALE => 'female',
        self::SCORING_JUNIOR => 'junior',
    ];

    private const API_VERSION = '1.0';

    public static function getPath(int $discipline, int $endPoint): string
    {
        return sprintf(
            '%s/%s/%s',
            self::URL_RANKING,
            self::getDiscipline($discipline),
            self::getEndpoint($endPoint)
        );
    }

    public static function getVersion(): string
    {
        return self::API_VERSION;
    }

    public static function getDiscipline(int $discipline): string
    {
        $result = self::DISCIPLINES[$discipline] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Discipline not recognized: '.$discipline);
        }

        return $result;
    }

    public static function getEndpoint(int $endpoint): string
    {
        $result = self::ENDPOINTS[$endpoint] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Endpoint not recognized: '.$endpoint);
        }

        return $result;
    }

    public static function getEndpointFromName(string $name): int
    {
        $name = strtolower($name);

        foreach (self::ENDPOINTS as $endpoint => $value) {
            if ($name === $value) {
                return $endpoint;
            }
        }

        throw new \RuntimeException('Endpoint not found '.$name);
    }

    public static function getRegion(int $region): string
    {
        $result = self::REGIONS[$region] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Region id not recognized: '.$region);
        }

        return $result;
    }

    public static function getScoring(int $scoring): string
    {
        $result = self::SCORING[$scoring] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Scoring type not recognized: '.$scoring);
        }

        return $result;
    }

    /**
     * @return array<string, string|null|int>
     */
    public static function getMeta(int $discipline, int $endpoint): array
    {
        return [
            'endpoint' => self::getEndpoint($endpoint),
            'discipline' => self::getDiscipline($discipline),
            'ranking_date' => null,
            'count' => 0,
            'version' => self::getVersion()
        ];
    }
}
