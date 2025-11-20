<?php

namespace Balintpethe\LaravelUniversalScraper\Tests\Unit;

use Balintpethe\LaravelUniversalScraper\Parsing\HtmlExtractor;

it('can extract a simple product list from html', function () {
    $html = <<<HTML
    <div class="product-card">
        <h3 class="product-title">iPhone 13 128GB</h3>
        <span class="product-price">129 EUR</span>
        <a href="/iphone-13-128">
            <img src="/images/iphone-13.jpg" alt="">
        </a>
    </div>
    <div class="product-card">
        <h3 class="product-title">iPhone 12 64GB</h3>
        <span class="product-price">99 EUR</span>
        <a href="/iphone-12-64">
            <img src="/images/iphone-12.jpg" alt="">
        </a>
    </div>
    HTML;

    $extractor = new HtmlExtractor();

    $definition = [
        'item' => '.product-card',
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
    ];

    $items = $extractor->extractList($html, 'https://example.com', $definition);

    expect($items)->toHaveCount(2);

    expect($items[0]['title'])->toBe('iPhone 13 128GB');
    expect($items[0]['price'])->toBe(129.0);
    expect($items[0]['url'])->toBe('https://example.com/iphone-13-128');
    expect($items[0]['image'])->toBe('https://example.com/images/iphone-13.jpg');

    expect($items[1]['title'])->toBe('iPhone 12 64GB');
    expect($items[1]['price'])->toBe(99.0);
});
