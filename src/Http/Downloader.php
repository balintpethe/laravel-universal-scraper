<?php

namespace Balintpethe\LaravelUniversalScraper\Http;

use Illuminate\Support\Facades\Http;

class Downloader
{
    public function __construct(
        protected array $config = []
    ) {
    }

    public function get(string $url, array $query = [], array $headers = []): string
    {
        $clientConfig = $this->config['client'] ?? [];

        $timeout    = $clientConfig['timeout'] ?? 10;
        $retryTimes = $clientConfig['retry']['times'] ?? 0;
        $retrySleep = $clientConfig['retry']['sleep'] ?? 0;
        $defaultHeaders = $clientConfig['headers'] ?? [];

        $request = Http::withHeaders(array_merge($defaultHeaders, $headers))
            ->timeout($timeout);

        if ($retryTimes > 0) {
            $request = $request->retry($retryTimes, $retrySleep);
        }

        $response = $request->get($url, $query);

        $response->throw();

        return $response->body();
    }
}

