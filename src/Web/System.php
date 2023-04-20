<?php declare(strict_types=1);

namespace Wprs\Api\Web;

use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Nations\Nations;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;

class System
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

    public const ENDPOINT_PILOTS = Pilots::class;
    public const ENDPOINT_NATIONS = Nations::class;
    public const ENDPOINT_COMPETITION = Competition::class;
    public const ENDPOINT_COMPETITIONS = Competitions::class;

    private const ENDPOINTS = [
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

    public const PARAM_DATE = 0;
    public const PARAM_ID = 1;

    private const API_VERSION = '1.0';

    public static function getPath(int $discipline, string $endPoint): string
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

    public static function getDisciplineForDisplay(int $discipline): string
    {
        $result = self::getDiscipline($discipline);

        if ($result === 'paragliding-aerobatics') {
            $result = 'paragliding-acro-solo';
        } elseif ($discipline !== self::DISCIPLINE_PG_XC) {
            if (substr($result, -3) === '-xc') {
                $result = substr($result, 0, -3);
            }
        }

        return $result;
    }

    public static function getEndpoint(string $endpoint): string
    {
        $result = self::ENDPOINTS[$endpoint] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Endpoint not recognized: '.$endpoint);
        }

        return $result;
    }

    public static function getRegion(int $region): string
    {
        $result = self::REGIONS[$region] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Region id not recognized: '.$region);
        }

        return $result;
    }

    public static function getRankingDate(?string $rankingDate): string
    {
        $tz = new \DateTimeZone('UTC');

        if (null === $rankingDate) {
            $date = new \DateTime('now', $tz);
        } else {
            $date = \DateTime::createFromFormat('Y-m-d', $rankingDate, $tz);
        }

        if (false === $date) {
            throw new \RuntimeException('Invalid ranking date: '.$rankingDate);
        }

        return $date->format('Y-m-01');
    }

    /**
     * Checks batch parameter values
     *
     * @param array<mixed> $values
     * @throws \RuntimeException
     */
    public static function checkParams(array $values, int $type): void
    {
        $isRankingDate = $type === self::PARAM_DATE;
        $name = $isRankingDate ? 'ranking date' : 'competition id';
        $valuesCount = count($values);

        if ($valuesCount === 0) {
            throw new \RuntimeException(sprintf('Missing %ss', $name));
        }

        foreach ($values as $value) {
            // check for null first
            if ($value === null) {
                throw new \RuntimeException(sprintf('Invalid %s: null', $name));
            }

            $result = false;
            $errorValue = gettype($value);

            if ($isRankingDate) {
                if (is_string($value)) {
                    $errorValue = $value;
                    $rankingDate = self::getRankingDate($value);
                    $result = $value === $rankingDate;
                }
            } else {
                $result = is_int($value);
            }

            if (!$result) {
                throw new \RuntimeException(sprintf('Invalid %s: %s', $name, $errorValue));
            }
        }

        $unique = array_unique($values);

        if (count($unique) !== $valuesCount) {
            throw new \RuntimeException(sprintf('Duplicate %ss', $name));
        }
    }

    public static function getScoring(int $scoring): string
    {
        $result = self::SCORING[$scoring] ?? null;

        if (null === $result) {
            throw new \RuntimeException('Scoring type not recognized: '.$scoring);
        }

        return $result;
    }

    public static function formatMessage(string $className, string $error): string
    {
        $name = basename(strtr($className, '\\', '/'));

        return sprintf('%s: %s', $name, $error);
    }

    public static function getExceptionMessage(\Exception $e): string
    {
        if ($e instanceof WprsException) {
            return $e->getMessage();
        }

        return WprsException::formatMessage($e);
    }

    /**
     * @param array<mixed> $data
     */
    public static function toJson(array $data, bool $pretty): string
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $options |= $pretty ? JSON_PRETTY_PRINT : 0;

        $result = json_encode($data, $options);

        if ($result === false) {
            throw new \RuntimeException(json_last_error_msg());
        }

        return $result;
    }
}
