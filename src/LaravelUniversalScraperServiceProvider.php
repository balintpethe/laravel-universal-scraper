<?php

namespace Balintpethe\LaravelUniversalScraper;

use Illuminate\Support\ServiceProvider;
use Balintpethe\LaravelUniversalScraper\Http\Downloader;
use Balintpethe\LaravelUniversalScraper\Parsing\HtmlExtractor;

class LaravelUniversalScraperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/universal-scraper.php',
            'universal-scraper'
        );

        $this->app->singleton(ScraperManager::class, function ($app) {
            $config     = $app['config']->get('universal-scraper', []);
            $downloader = new Downloader($config);
            $extractor  = new HtmlExtractor();

            return new ScraperManager($config, $downloader, $extractor);
        });

        $this->app->alias(ScraperManager::class, 'universal-scraper.manager');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/universal-scraper.php' => config_path('universal-scraper.php'),
            ], 'universal-scraper-config');
        }
    }
}

