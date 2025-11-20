<?php

namespace Balintpethe\LaravelUniversalScraper\Http;

use Illuminate\Support\Facades\Http;

class AuthenticatedDownloader extends Downloader
{
    protected array $profileConfig;
    protected bool $authenticated = false;

    /**
     * Egyszerű cookie jar – kulcs: cookie név, érték: cookie érték.
     */
    protected array $cookies = [];

    public function __construct(array $config, array $profileConfig)
    {
        parent::__construct($config);
        $this->profileConfig = $profileConfig;
    }

    public function get(string $url, array $query = [], array $headers = []): string
    {
        $this->authenticateIfNeeded();

        $clientConfig   = $this->config['client'] ?? [];
        $defaultHeaders = $clientConfig['headers'] ?? [];

        $request = Http::withHeaders(array_merge($defaultHeaders, $headers))
            ->timeout($clientConfig['timeout'] ?? 10);

        // Ha van session cookie, azt is felrakjuk
        if (!empty($this->cookies)) {
            $host = parse_url($url, PHP_URL_HOST);

            if ($host) {
                $request = $request->withCookies($this->cookies, $host);
            }
        }

        if (!empty($clientConfig['retry']['times'] ?? 0)) {
            $request = $request->retry(
                $clientConfig['retry']['times'],
                $clientConfig['retry']['sleep'] ?? 0
            );
        }

        $response = $request->get($url, $query);
        $response->throw();

        return $response->body();
    }

    protected function authenticateIfNeeded(): void
    {
        if ($this->authenticated) {
            return;
        }

        $auth = $this->profileConfig['auth'] ?? null;

        // Ha nincs auth config, akkor úgy viselkedünk, mint egy sima Downloader
        if (!$auth) {
            $this->authenticated = true;

            return;
        }

        $type = $auth['type'] ?? 'form';

        if ($type === 'form') {
            $this->loginWithForm($auth);
        }

        // ide később jöhet token alapú auth is (pl. bearer token)

        $this->authenticated = true;
    }

    /**
     * Klasszikus form alapú login (email + password).
     */
    protected function loginWithForm(array $auth): void
    {
        $baseUrl  = rtrim($this->profileConfig['base_url'] ?? '', '/');
        $loginUrl = $baseUrl . '/' . ltrim($auth['login_url'] ?? '/login', '/');

        $emailEnv    = $auth['credentials']['email_env'] ?? null;
        $passwordEnv = $auth['credentials']['password_env'] ?? null;

        $email    = $emailEnv ? env($emailEnv) : null;
        $password = $passwordEnv ? env($passwordEnv) : null;

        $fields = $auth['fields'] ?? [];

        $payload = [
                $fields['email']    ?? 'email'    => $email,
                $fields['password'] ?? 'password' => $password,
        ];

        $clientConfig   = $this->config['client'] ?? [];
        $defaultHeaders = $clientConfig['headers'] ?? [];

        $request = Http::withHeaders($defaultHeaders)
            ->asForm()
            ->timeout($clientConfig['timeout'] ?? 10);

        if (!empty($clientConfig['retry']['times'] ?? 0)) {
            $request = $request->retry(
                $clientConfig['retry']['times'],
                $clientConfig['retry']['sleep'] ?? 0
            );
        }

        $response = $request->post($loginUrl, $payload);
        $response->throw();

        // Cookiek begyűjtése (Symfony Cookie objektumokból név => érték)
        $cookies = [];

        foreach ($response->cookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $this->cookies = $cookies;
    }
}

