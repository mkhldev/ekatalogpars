<?php

namespace App\Services\Scrape;

use Goutte\Client;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;

class GoutteClient
{
    public ?Client $client = null;

    public array $defaultOptions = [
        'timeout' => 60,
        'verify_host' => false,
    ];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->client = new Client(
            HttpClient::create(array_merge($options, $this->defaultOptions))
        // History::class,
        // CookieJar::class
        );
    }

    /**
     * Undocumented function
     *
     * @param string $uri
     * @param array $parameters
     * @return Crawler
     */
    public function get(string $uri, array $parameters = []): Crawler
    {
        return $this->client->request('GET', $uri, $parameters);
    }
}
