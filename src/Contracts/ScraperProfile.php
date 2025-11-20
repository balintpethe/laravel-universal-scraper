<?php

namespace Balintpethe\LaravelUniversalScraper\Contracts;

use Illuminate\Support\Collection;

interface ScraperProfile
{
    public function name(): string;

    /**
     * Lista lekérdezése (pl. termékek).
     */
    public function list(array $options = []): Collection;

    /**
     * Részletes view egy adott URL-re (pl. termék oldal).
     */
    public function detail(string $url, array $options = []): array;
}

