<?php

namespace Balintpethe\LaravelUniversalScraper\Tests\Unit;

use Balintpethe\LaravelUniversalScraper\Http\AuthenticatedDownloader;
use Balintpethe\LaravelUniversalScraper\Http\Downloader;
use Balintpethe\LaravelUniversalScraper\Parsing\HtmlExtractor;
use Balintpethe\LaravelUniversalScraper\ScraperManager;
use Balintpethe\LaravelUniversalScraper\Tests\Support\FakeProfile;

it('uses plain Downloader for profiles without auth', function () {
    $config = [
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
        'profiles' => [
            'plain_profile' => [
                'base_url' => 'https://example.com',
                'class'    => FakeProfile::class,
            ],
        ],
    ];

    $downloader = new Downloader($config);
    $extractor  = new HtmlExtractor();

    $manager = new ScraperManager($config, $downloader, $extractor);

    FakeProfile::$lastDownloader = null;

    // csak annyi a lényeg, hogy a profile() példányosítsa a FakeProfile-t
    $manager->profile('plain_profile');

    expect(FakeProfile::$lastDownloader)->toBeInstanceOf(Downloader::class);
    expect(FakeProfile::$lastDownloader)->not->toBeInstanceOf(AuthenticatedDownloader::class);
});

it('uses AuthenticatedDownloader for profiles with auth', function () {
    $config = [
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
        'profiles' => [
            'auth_profile' => [
                'base_url' => 'https://example-secure.com',
                'class'    => FakeProfile::class,
                'auth' => [
                    'type'      => 'form',
                    'login_url' => '/login',
                    'credentials' => [
                        'email_env'    => 'SCRAPER_LOGIN_EMAIL',
                        'password_env' => 'SCRAPER_LOGIN_PASSWORD',
                    ],
                    'fields' => [
                        'email'    => 'email',
                        'password' => 'password',
                    ],
                ],
            ],
        ],
    ];

    $downloader = new Downloader($config);
    $extractor  = new HtmlExtractor();

    $manager = new ScraperManager($config, $downloader, $extractor);

    FakeProfile::$lastDownloader = null;

    $manager->profile('auth_profile');

    expect(FakeProfile::$lastDownloader)->toBeInstanceOf(AuthenticatedDownloader::class);
});
