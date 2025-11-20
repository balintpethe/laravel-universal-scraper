<?php

namespace Balintpethe\LaravelUniversalScraper\Profiles;

use Illuminate\Support\Collection;
use Balintpethe\LaravelUniversalScraper\Contracts\ScraperProfile;
use Balintpethe\LaravelUniversalScraper\Http\Downloader;
use Balintpethe\LaravelUniversalScraper\Parsing\HtmlExtractor;

class ConfigProfile implements ScraperProfile
{
    public function __construct(
        protected string $name,
        protected array $config,
        protected Downloader $downloader,
        protected HtmlExtractor $extractor
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function list(array $options = []): Collection
    {
        $baseUrl   = rtrim($this->config['base_url'] ?? '', '/');
        $listConfig = $this->config['list'] ?? [];

        $path = $listConfig['path'] ?? '/';
        $url  = $baseUrl . '/' . ltrim($path, '/');

        $query = $options['query'] ?? [];

        if (isset($listConfig['query'])) {
            $query = array_merge($listConfig['query'], $query);
        }

        $headers = $options['headers'] ?? [];

        $html = $this->downloader->get($url, $query, $headers);

        return $this->extractor->extractList($html, $baseUrl, $listConfig);
    }

    public function detail(string $url, array $options = []): array
    {
        $headers = $options['headers'] ?? [];
        $query   = $options['query'] ?? [];

        $html = $this->downloader->get($url, $query, $headers);

        // Alap implementáció: csak a nyers HTML-t adja vissza,
        // ha kell, ezt lehet override-olni egy saját Profile osztályban.
        return [
            'url'  => $url,
            'html' => $html,
        ];
    }
}

