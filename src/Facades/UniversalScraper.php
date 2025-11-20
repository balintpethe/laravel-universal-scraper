<?php

namespace Balintpethe\LaravelUniversalScraper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection list(string $profile, array $options = [])
 * @method static array detail(string $profile, string $url, array $options = [])
 * @method static \Balintpethe\LaravelUniversalScraper\Contracts\ScraperProfile profile(string $profile)
 */
class UniversalScraper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'universal-scraper.manager';
    }
}

