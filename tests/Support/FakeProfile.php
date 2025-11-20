<?php

namespace Balintpethe\LaravelUniversalScraper\Tests\Support;

use Illuminate\Support\Collection;
use Balintpethe\LaravelUniversalScraper\Contracts\ScraperProfile;
use Balintpethe\LaravelUniversalScraper\Http\Downloader;

class FakeProfile implements ScraperProfile
{
    public static ?Downloader $lastDownloader = null;

    public function __construct(
        protected string $name,
        protected array $config,
        Downloader $downloader
    ) {
        self::$lastDownloader = $downloader;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function list(array $options = []): Collection
    {
        return collect();
    }

    public function detail(string $url, array $options = []): array
    {
        return [];
    }
}

