<?php

namespace Balintpethe\LaravelUniversalScraper\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Balintpethe\LaravelUniversalScraper\LaravelUniversalScraperServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelUniversalScraperServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // ide tehetsz extra configot, ha a tesztek igénylik
        $app['config']->set('universal-scraper', [
            'client' => [
                'timeout' => 5,
                'retry' => [
                    'times' => 0,
                    'sleep' => 0,
                ],
                'headers' => [
                    'User-Agent' => 'LaravelUniversalScraper/Test',
                ],
            ],
            'profiles' => [],
        ]);
    }
}
