<?php

namespace Balintpethe\LaravelUniversalScraper;

use Illuminate\Support\Collection;
use Balintpethe\LaravelUniversalScraper\Contracts\ScraperProfile;
use Balintpethe\LaravelUniversalScraper\Exceptions\ProfileNotFoundException;
use Balintpethe\LaravelUniversalScraper\Http\Downloader;
use Balintpethe\LaravelUniversalScraper\Http\AuthenticatedDownloader;
use Balintpethe\LaravelUniversalScraper\Parsing\HtmlExtractor;
use Balintpethe\LaravelUniversalScraper\Profiles\ConfigProfile;

class ScraperManager
{
    public function __construct(
        protected array $config,
        protected Downloader $downloader,
        protected HtmlExtractor $extractor
    ) {
    }

    public function profile(string $name): ScraperProfile
    {
        $profiles      = $this->config['profiles'] ?? [];
        $profileConfig = $profiles[$name] ?? null;

        if (!$profileConfig) {
            throw new ProfileNotFoundException("Scraper profile [{$name}] is not configured.");
        }

        $class = $profileConfig['class'] ?? ConfigProfile::class;

        if (!is_subclass_of($class, ScraperProfile::class)) {
            throw new \InvalidArgumentException(
                "Scraper profile class [{$class}] must implement ScraperProfile interface."
            );
        }

        $downloader = $this->makeDownloader($profileConfig);

        return new $class($name, $profileConfig, $downloader, $this->extractor);
    }

    /**
     * Ha a profil tartalmaz 'auth' blokkot, AuthenticatedDownloader-t hozunk létre,
     * különben a sima, singleton Downloader-t használjuk.
     */
    protected function makeDownloader(array $profileConfig): Downloader
    {
        if (!empty($profileConfig['auth'])) {
            return new AuthenticatedDownloader($this->config, $profileConfig);
        }

        return $this->downloader;
    }

    public function list(string $profile, array $options = []): Collection
    {
        return $this->profile($profile)->list($options);
    }

    public function detail(string $profile, string $url, array $options = []): array
    {
        return $this->profile($profile)->detail($url, $options);
    }
}
