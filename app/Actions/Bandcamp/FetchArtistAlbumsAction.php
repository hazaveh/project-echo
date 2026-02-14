<?php

namespace App\Actions\Bandcamp;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class FetchArtistAlbumsAction
{
    private const MUSIC_URL = 'https://%s.bandcamp.com/music';

    public function execute(string $username): array
    {
        $url = sprintf(self::MUSIC_URL, $username);

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ])->timeout(10)
            ->connectTimeout(5)
            ->get($url);

        if ($response->failed()) {
            $status = $response->status();
            $retryAfter = $response->header('Retry-After');

            throw new \RuntimeException(sprintf(
                'Bandcamp artist albums request failed [url=%s status=%d retry_after=%s].',
                $url,
                $status,
                $retryAfter !== null && $retryAfter !== '' ? $retryAfter : 'n/a'
            ));
        }

        $crawler = new Crawler($response->body());

        $payload = [
            'artist' => [
                'name' => $this->textFrom($crawler, '.band-name-location .title'),
                'location' => $this->textFrom($crawler, '.band-name-location .location'),
            ],
            'bandcamp_url' => sprintf('https://%s.bandcamp.com', $username),
            'username' => $username,
            'photo' => $this->attributeFrom($crawler, '#band-photo img', 'src')
                ?? $this->attributeFrom($crawler, '#bio-container img', 'src'),
            'albums' => $this->albums($crawler, $username),
        ];

        if ($payload['artist']['name'] === null && count($payload['albums']) === 0) {
            throw new \RuntimeException(sprintf(
                'Bandcamp artist albums page parse failed [url=%s reason=missing_expected_markup].',
                $url
            ));
        }

        return $payload;
    }

    /**
     * @return array<int, array{name: ?string, type: ?string, slug: ?string, url: ?string}>
     */
    private function albums(Crawler $crawler, string $username): array
    {
        $items = [];

        $crawler->filter('#music-grid .music-grid-item')->each(function (Crawler $node) use (&$items, $username): void {
            $url = $this->albumUrl($node, $username);

            $items[] = [
                'name' => $this->textFrom($node, '.title'),
                'type' => $this->albumType($node, $url),
                'slug' => $this->albumSlug($url),
                'url' => $url,
            ];
        });

        return $items;
    }

    private function textFrom(Crawler $crawler, string $selector): ?string
    {
        $node = $crawler->filter($selector);

        if (! $node->count()) {
            return null;
        }

        $value = trim($node->first()->text(''));

        return $value === '' ? null : $value;
    }

    private function attributeFrom(Crawler $crawler, string $selector, string $attribute): ?string
    {
        $node = $crawler->filter($selector);

        if (! $node->count()) {
            return null;
        }

        $value = trim($node->first()->attr($attribute) ?? '');

        return $value === '' ? null : $value;
    }

    private function albumUrl(Crawler $crawler, string $username): ?string
    {
        $url = $this->attributeFrom($crawler, 'a', 'href');

        if ($url === null) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (! str_starts_with($url, '/')) {
            $url = '/'.$url;
        }

        return sprintf('https://%s.bandcamp.com%s', $username, $url);
    }

    private function albumType(Crawler $crawler, ?string $url): ?string
    {
        $nodeType = $this->textFrom($crawler, '.itemtype');

        if ($nodeType !== null) {
            $normalizedNodeType = strtolower(trim($nodeType, " \t\n\r\0\x0B()"));

            if ($normalizedNodeType === 'album') {
                return 'album';
            }

            if (in_array($normalizedNodeType, ['single', 'track'], true)) {
                return 'single';
            }

            return null;
        }

        if ($url === null) {
            return null;
        }

        if (str_contains($url, '/album/')) {
            return 'album';
        }

        if (str_contains($url, '/track/')) {
            return 'single';
        }

        return null;
    }

    private function albumSlug(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        if (count($segments) < 2) {
            return null;
        }

        $entityType = $segments[0];

        if (! in_array($entityType, ['album', 'track'], true)) {
            return null;
        }

        $slug = $segments[1] ?? null;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        return $slug;
    }
}
