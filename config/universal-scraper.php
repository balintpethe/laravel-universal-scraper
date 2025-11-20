<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP kliens beállítások
    |--------------------------------------------------------------------------
    */

    'client' => [
        'timeout' => 10,

        'retry' => [
            'times' => 2,
            'sleep' => 200, // ms
        ],

        'headers' => [
            'User-Agent' => 'LaravelUniversalScraper/1.0 (+https://example.com)',
            'Accept' => 'text/html,application/xhtml+xml',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scraper profile-ok
    |--------------------------------------------------------------------------
    |
    | Minden profil egy weboldal / endpoint szabályait írja le.
    | A "ConfigProfile" egy deklaratív, selector-alapú megoldás.
    |
    | Ha tartalmazza a profil az 'auth' blokkot, akkor a ScraperManager
    | az AuthenticatedDownloader-t használja.
    |
    */

    'profiles' => [
        'example_authenticated_site' => [
            'base_url' => 'https://example.com',

            'class' => \Balintpethe\LaravelUniversalScraper\Profiles\ConfigProfile::class,

            'auth' => [
                'type'      => 'form',   // jelenleg: 'form' támogatott
                'login_url' => '/login',
                'method'    => 'POST',

                // A credential értékeket NEM írjuk a configba, csak env-ből olvassuk:
                'credentials' => [
                    'email_env'    => 'SCRAPER_LOGIN_EMAIL',
                    'password_env' => 'SCRAPER_LOGIN_PASSWORD',
                ],

                // A form mezők nevei
                'fields' => [
                    'email'    => 'email',
                    'password' => 'password',
                ],
            ],

            'list' => [
                'path' => '/iphone',
                'query' => [],

                // CSS selector, ami az egyes itemeket jelöli
                'item' => '.product-card',

                // Mezők definíciója
                'fields' => [
                    'title' => [
                        'selector' => '.product-title',
                        'attr'     => 'text',
                    ],
                    'price' => [
                        'selector' => '.product-price',
                        'attr'     => 'text',
                        'cast'     => 'float',
                    ],
                    'url' => [
                        'selector'     => 'a',
                        'attr'         => 'href',
                        'absolute_url' => true,
                    ],
                    'image' => [
                        'selector'     => 'img',
                        'attr'         => 'src',
                        'absolute_url' => true,
                    ],
                ],
            ],
        ],

        'example_iphone_list' => [
            'base_url' => 'https://example.com',

            'class' => \Balintpethe\LaravelUniversalScraper\Profiles\ConfigProfile::class,

            'list' => [
                'path' => '/iphone',
                'query' => [],

                // CSS selector, ami az egyes itemeket jelöli
                'item' => '.product-card',

                // Mezők definíciója
                'fields' => [
                    'title' => [
                        'selector' => '.product-title',
                        'attr'     => 'text',
                    ],
                    'price' => [
                        'selector' => '.product-price',
                        'attr'     => 'text',
                        'cast'     => 'float',
                    ],
                    'url' => [
                        'selector'     => 'a',
                        'attr'         => 'href',
                        'absolute_url' => true,
                    ],
                    'image' => [
                        'selector'     => 'img',
                        'attr'         => 'src',
                        'absolute_url' => true,
                    ],
                ],
            ],
        ],

    ],

];

