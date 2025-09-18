<?php

namespace App\Actions\Bandcamp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class FindBandAction
{
    const SEARCH_URL = 'https://bandcamp.com/search?q=%s&item_type=b';

    public function execute(string $name): array
    {
        try {
            $url = sprintf(self::SEARCH_URL, urlencode($name));

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ])->withoutVerifying()
                ->get($url);

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch search results: '.$response->status());
            }

            $crawler = new Crawler($response->body());

            $results = [];

            $res = $crawler->filter('.result-items')
                ->children()->each(function (Crawler $node) use (&$results) {
                    $artist = $node->filter('.result-info');
                    $res = [
                        'name' => $artist->filter('.heading a')->text(),
                        'origin' => $artist->filter('.subhead')->text(),
                        'genre' => $artist->filter('.genre')->text(),
                        'url' => $artist->filter('.itemurl')->text(),
                        'tags' => trim(str_replace('tags', '', $artist->filter('.tags')->text())),
                    ];
                    $results[] = $res;
                });

            return $results;

        } catch (\Exception $e) {
            Log::error('Bandcamp search failed: '.$e->getMessage());
            throw $e;
        }
    }
}
