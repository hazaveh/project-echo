<?php

namespace App\Actions\Bandcamp;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class SearchAlbumAction
{
    const SEARCH_URL = 'https://bandcamp.com/search?q=%s&item_type=a';
    public function execute(string $name): array
    {
        $body = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ])->get(sprintf(self::SEARCH_URL, urlencode($name)));

        $crawler = new Crawler($body->body());

        $results = [];

        $crawler->filter('.result-items')->children()->each(function(Crawler $node) use (&$results) {
            $album = $node->filter('.result-info');
            $res = [
                "name" => $album->filter('.heading a')->text(),
                "artist" => $album->filter('.subhead')->text(),
                "url" => $album->filter('.itemurl')->text(),
            ];
            $results[] = $res;
        });

        return $results;
    }
}
