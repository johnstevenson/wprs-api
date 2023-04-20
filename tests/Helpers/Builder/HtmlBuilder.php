<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\HttpUtils;
use Wprs\Api\Http\Response;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Competition\CompetitionParams;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competitions\CompetitionsParams;
use Wprs\Api\Web\Endpoint\Competitions\CompetitionsParser;
use Wprs\Api\Web\Endpoint\Nations\Nations;
use Wprs\Api\Web\Endpoint\Nations\NationsParams;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\Endpoint\Pilots\PilotsParams;
use Wprs\Api\Web\Endpoint\Pilots\PilotsParser;
use Wprs\Api\Web\System;

class HtmlBuilder
{
    private int $discipline;
    private int $regionId;
    private ?int $nationId;
    private int $compId;
    private int $pilotsCount;
    private int $pilotsMax;
    private string $rankingDate;
    private Config $config;
    private HttpDownloader $downloader;
    private HtmlFormatter $formatter;

    public function __construct()
    {
        $this->config = new Config();
        $this->discipline = $this->config->getDiscipline();
        $this->rankingDate = $this->config->getRankingDate();
        $this->regionId = $this->config->getRegionId();
        $this->nationId = $this->config->getNationId();

        $this->downloader = new HttpDownloader();
        $this->formatter = new HtmlFormatter();
    }

    public function build(): void
    {
        $pages = $this->getHtmlPages();

        foreach ($pages as $name => $html) {
            $html = $this->formatter->format($html);
            $file = Utils::getHtmlFile($name);
            Utils::saveToFile($file, $html);
        }

        $data = $this->config->getData($this->pilotsCount, $this->pilotsMax, $this->compId);
        $configFile = Utils::getConfigFile();

        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        Utils::saveToFile($configFile, $json);
    }

    /**
     * @return array<string>
     */
    private function getHtmlPages(): array
    {
        $urls = [
            $this->getPilotsUrl(),
            $this->getCompetitionsUrl(),
            $this->getNationsUrl(),
        ];

        $names = ['pilots', 'competitions', 'nations'];
        $pages = [];

        $responses = $this->downloader->getBatch($urls);

        /** @var Response $response */
        foreach ($responses as $response) {
            $name = $names[$response->id];
            $pages[$name] = HttpUtils::getResponseContent($response);
        }

        list($this->pilotsCount, $this->pilotsMax) = $this->parsePilots($pages['pilots']);
        $this->compId = $this->parseCompetitions($pages['competitions']);
        $url = $this->getCompetitionUrl($this->compId);
        $response = $this->downloader->get($url);
        $pages['competition'] = HttpUtils::getResponseContent($response);

        return $pages;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parsePilots(string $html): array
    {
        $parser = new PilotsParser();
        $data = $parser->parse($html);

        return [$data->getOverallCount(), $data->getItemCount()];
    }

    private function parseCompetitions(string $html): int
    {
        $parser = new CompetitionsParser();
        $data = $parser->parse($html);
        $items = $data->getItems();

        $compId = null;

        foreach ($items as $item) {
            if ($item['tasks'] !== 0) {
                $compId = $item['id'];
                break;
            }
        }

        if (!is_integer($compId)) {
            throw new \RuntimeException('Unable to find a comp id');
        }

        return $compId;
    }

    private function getCompetitionUrl(int $id): string
    {
        $path = System::getPath($this->discipline, Competition::class);
        $params = new CompetitionParams($id);
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }

    private function getCompetitionsUrl(): string
    {
        $path = System::getPath($this->discipline, Competitions::class);
        $params = new CompetitionsParams();
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }

    private function getNationsUrl(): string
    {
        $path = System::getPath($this->discipline, Nations::class);
        $params = new NationsParams($this->regionId);
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }

    private function getPilotsUrl(): string
    {
        $path = System::getPath($this->discipline, Pilots::class);
        $params = new PilotsParams($this->regionId, $this->nationId);
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }
}
