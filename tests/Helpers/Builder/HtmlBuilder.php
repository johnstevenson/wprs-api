<?php declare(strict_types=1);

namespace Wprs\Api\Tests\Helpers\Builder;

use Wprs\Api\Http\HttpDownloader;
use Wprs\Api\Http\HttpUtils;
use Wprs\Api\Tests\Helpers\Utils;
use Wprs\Api\Web\Endpoint\Competition\Competition;
use Wprs\Api\Web\Endpoint\Competition\CompetitionParams;
use Wprs\Api\Web\Endpoint\Competitions\Competitions;
use Wprs\Api\Web\Endpoint\Competitions\CompetitionsParams;
use Wprs\Api\Web\Endpoint\Competitions\CompetitionsParser;
use Wprs\Api\Web\Endpoint\Pilots\Pilots;
use Wprs\Api\Web\Endpoint\Pilots\PilotsParams;
use Wprs\Api\Web\System;

class HtmlBuilder
{
    private int $discipline;
    private int $regionId;
    private string $rankingDate;
    private BuildConfig $config;
    private HttpDownloader $downloader;
    private HtmlFormatter $formatter;

    public function __construct()
    {
        $this->config = new BuildConfig();
        $this->discipline = $this->config->getDiscipline();
        $this->rankingDate = $this->config->getRankingDate();
        $this->regionId = $this->config->getRegionId();

        $this->downloader = new HttpDownloader();
        $this->formatter = new HtmlFormatter();
    }

    public function build(): void
    {
        $pages = $this->getHtmlPages();

        $files = [
            Utils::getHtmlFile('pilots'),
            Utils::getHtmlFile('competitions'),
            Utils::getHtmlFile('competition'),
        ];

        foreach ($pages as $index => $html) {
            $html = $this->formatter->format($html);
            $file = $files[$index];
            file_put_contents($file, $html);
        }

        $this->config->save(Utils::getConfigFile());
    }

    /**
     * @return array<string>
     */
    protected function getHtmlPages(): array
    {
        $urls = [
            $this->getPilotsUrl(),
            $this->getCompetitionsUrl()
        ];

        $pages = [];

        $responses = $this->downloader->getBatch($urls);

        foreach ($responses as $response) {
            $pages[] = HttpUtils::getResponseContent($response);
        }

        $compId = $this->parseCompetitions($pages[1]);
        $this->config->setCompId($compId);

        $url = $this->getCompetitionUrl($compId);
        $response = $this->downloader->get($url);
        $pages[] = HttpUtils::getResponseContent($response);

        return $pages;
    }

    protected function parseCompetitions(string $html): int
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

    protected function getCompetitionUrl(int $id): string
    {
        $path = System::getPath($this->discipline, Competition::class);
        $params = new CompetitionParams($id);
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }

    protected function getCompetitionsUrl(): string
    {
        $path = System::getPath($this->discipline, Competitions::class);
        $params = new CompetitionsParams();
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }

    protected function getPilotsUrl(): string
    {
        $path = System::getPath($this->discipline, Pilots::class);
        $params = new PilotsParams($this->regionId);
        $query = $params->getQueryParams($this->rankingDate);

        return HttpUtils::buildQuery($query, $path);
    }
}
