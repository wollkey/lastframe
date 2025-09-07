<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Component\DomCrawler\Crawler;

final readonly class HttpClient implements HttpClientInterface
{
    private const string BASE_URI = 'https://letterboxd.com/';
    private const string USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36';

    private Client $client;
    private CookieJar $cookies;

    public function __construct(private string $username, private string $password)
    {
        $this->cookies = new CookieJar(true);
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'cookies' => $this->cookies,
            'allow_redirects' => true,
            'headers' => ['User-Agent' => self::USER_AGENT]
        ]);
    }

    public function get(string $path): string
    {
        $this->ensureAuthenticated();

        return (string) $this->client->get($path)->getBody();
    }

    public function post($path, array $options): string
    {
        $this->ensureAuthenticated();

        return (string) $this->client->post($path, $options)->getBody();
    }

    private function ensureAuthenticated(): void
    {
        if (count($this->cookies->toArray()) <= 0) {
            $this->authenticate();
        }
    }

    private function authenticate(): void
    {
        $csrf = $this->getCsrfToken();

        $response = $this->client->post('user/login.do', [
            'form_params' => [
                '__csrf' => $csrf,
                'authenticationCode' => '',
                'username' => $this->username,
                'password' => $this->password,
            ],
            'headers' => [
                'Accept' => 'application/json, text/javascript, */*; q=0.01',
                'Accept-Language' => 'en-US,en;q=0.9,ru;q=0.8',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin' => 'https://letterboxd.com',
                'Referer' => 'https://letterboxd.com/',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
            'allow_redirects' => false,
        ]);

        if (!in_array($response->getStatusCode(), [200, 302], true)) {
            throw new \RuntimeException('Authentication failed');
        }
    }

    private function getCsrfToken(): string
    {
        foreach ($this->cookies->toArray() as $cookie) {
            if ($cookie['Name'] === 'com.xk72.webparts.csrf') {
                return $cookie['Value'];
            }
        }

        $html = (string) $this->client->get('')->getBody();
        $crawler = new Crawler($html);
        $csrfInput = $crawler->filter('input[name="__csrf"]');

        return $csrfInput->attr('value') ?? throw new \RuntimeException('CSRF token not found');
    }
}