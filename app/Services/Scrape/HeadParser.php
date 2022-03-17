<?php

namespace App\Services\Scrape;

use Symfony\Component\DomCrawler\Crawler;

class HeadParser
{
    public Crawler $crawler;

    public array $metas = [
        'keywords',
        'description',
    ];

    public array $property = [
        'og:title',
        'og:type',
        'og:url',
        'og:image',
        'og:site_name',
        'og:description',
    ];

    /**
     * @param Crawler $crawler
     */
    public function __construct(Crawler $crawler)
    {
        $this->crawler = $crawler;
    }

    public function getTitle(): string
    {
        $title = $this->crawler->filter('title');

        return $title->count() ? $title->text() : '';
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        $res = [];
        foreach ($this->metas as $meta) {
            $content = $this->crawler->filter("meta[name=\"{$meta}\"]");
            if ($content->count()) {
                $res[] = [
                    'name' => $meta,
                    'content' => $content->attr('content')
                ];
            }
        }

        foreach ($this->property as $property) {
            $content = $this->crawler->filter("meta[property=\"{$property}\"]");
            if ($content->count()) {
                $res[] = [
                    'property' => $property,
                    'content' => $content->attr('content')
                ];
            }
        }

        return $res;
    }
}
