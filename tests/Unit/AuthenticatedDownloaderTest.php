<?php

namespace Balintpethe\LaravelUniversalScraper\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Balintpethe\LaravelUniversalScraper\Http\AuthenticatedDownloader;

it('performs login before fetching protected page when auth config is present', function () {
    config()->set('universal-scraper.client', [
        'timeout' => 5,
        'retry' => [
            'times' => 0,
            'sleep' => 0,
        ],
        'headers' => [
            'User-Agent' => 'LaravelUniversalScraper/Test',
        ],
    ]);

    // Teszt kedvéért env-ek – normál esetben .env-ben lennének
    putenv('SCRAPER_LOGIN_EMAIL=scraper@example.com');
    putenv('SCRAPER_LOGIN_PASSWORD=super-secret');

    $profileConfig = [
        'base_url' => 'https://example-secure.com',
        'auth' => [
            'type'      => 'form',
            'login_url' => '/login',
            'method'    => 'POST',
            'credentials' => [
                'email_env'    => 'SCRAPER_LOGIN_EMAIL',
                'password_env' => 'SCRAPER_LOGIN_PASSWORD',
            ],
            'fields' => [
                'email'    => 'email',
                'password' => 'password',
            ],
        ],
    ];

    Http::fake([
        'https://example-secure.com/login' => Http::response('OK', 200, [
            // Itt elvileg set-cookie is jöhetne, de a downloader elvan nélküle is teszt szinten
        ]),
        'https://example-secure.com/secret-page' => Http::response('<html>secret</html>', 200),
    ]);

    $downloaderConfig = config('universal-scraper');

    $downloader = new AuthenticatedDownloader($downloaderConfig, $profileConfig);

    $body = $downloader->get('https://example-secure.com/secret-page');

    expect($body)->toBe('<html>secret</html>');

    // Ellenőrizzük, hogy volt login POST
    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://example-secure.com/login') {
            return false;
        }

        return $request->method() === 'POST'
            && $request['email'] === 'scraper@example.com'
            && $request['password'] === 'super-secret';
    });

    // És volt a protected GET
    Http::assertSent(function ($request) {
        return $request->url() === 'https://example-secure.com/secret-page'
            && $request->method() === 'GET';
    });
});

