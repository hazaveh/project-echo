<?php

namespace App\Actions\Bandcamp;

use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class FetchAlbumDetailsAction
{
    private const RELEASE_URL = 'https://%s.bandcamp.com/%s/%s';

    public function execute(string $username, string $albumSlug): array
    {
        [$url, $releaseType, $responseBody] = $this->fetchReleasePage($username, $albumSlug);

        return $this->buildPayload($username, $url, $releaseType, $responseBody);
    }

    public function executeByType(string $username, string $releaseType, string $albumSlug): array
    {
        [$url, $normalizedReleaseType, $responseBody] = $this->fetchReleasePageByType($username, $releaseType, $albumSlug);

        return $this->buildPayload($username, $url, $normalizedReleaseType, $responseBody);
    }

    private function buildPayload(string $username, string $url, string $releaseType, string $responseBody): array
    {
        $crawler = new Crawler($responseBody);
        $tracks = $this->tracks($crawler);

        if ($tracks === [] && $releaseType === 'track') {
            $singleTrack = $this->singleTrackFromJsonLd($responseBody);

            if ($singleTrack !== null) {
                $tracks = [$singleTrack];
            }
        }

        $album = $this->textFrom($crawler, '.trackTitle');

        if ($album === null && $tracks === []) {
            throw new \RuntimeException(sprintf(
                'Bandcamp release page parse failed [url=%s type=%s reason=missing_expected_markup].',
                $url,
                $releaseType
            ));
        }

        return [
            'album' => $album,
            'artist' => $this->textFrom($crawler, '#name-section span a'),
            'bandcamp_url' => $url,
            'username' => $username,
            'type' => $releaseType === 'track' ? 'single' : 'album',
            'photo' => $this->attributeFrom($crawler, '#tralbumArt img', 'src'),
            'track_count' => count($tracks),
            'tracks' => $tracks,
        ];
    }

    /**
     * @return array{number: int, title: ?string, length: ?string, length_seconds: ?int}|null
     */
    private function singleTrackFromJsonLd(string $responseBody): ?array
    {
        if (! preg_match('/<script\s+type="application\/ld\+json">\s*(\{.*?\})\s*<\/script>/s', $responseBody, $matches)) {
            return null;
        }

        $payload = json_decode($matches[1], true);

        if (! is_array($payload)) {
            return null;
        }

        $title = isset($payload['name']) && is_string($payload['name'])
            ? trim($payload['name'])
            : null;

        $lengthSeconds = $this->isoDurationToSeconds($payload['duration'] ?? null);

        return [
            'number' => 1,
            'title' => $title === '' ? null : $title,
            'length' => $this->secondsToClock($lengthSeconds),
            'length_seconds' => $lengthSeconds,
        ];
    }

    /**
     * @return array{string, string, string}
     */
    private function fetchReleasePage(string $username, string $albumSlug): array
    {
        $request = $this->httpRequest();
        $attempts = [];

        foreach (['album', 'track'] as $releaseType) {
            $url = sprintf(self::RELEASE_URL, $username, $releaseType, $albumSlug);
            $response = $request->get($url);
            $attempts[] = $this->attempt($url, $response->status(), $response->header('Retry-After'));

            if ($response->successful()) {
                return [$url, $releaseType, $response->body()];
            }
        }

        throw new \RuntimeException(sprintf(
            'Bandcamp release details request failed [attempts=%s].',
            json_encode($attempts)
        ));
    }

    /**
     * @return array{string, string, string}
     */
    private function fetchReleasePageByType(string $username, string $releaseType, string $albumSlug): array
    {
        $normalizedReleaseType = $this->normalizeReleaseType($releaseType);

        if ($normalizedReleaseType === null) {
            throw new \InvalidArgumentException('Invalid release type. Allowed types: album, track, single.');
        }

        $attempts = [];

        foreach ($this->preferredReleaseTypes($normalizedReleaseType) as $candidateReleaseType) {
            $url = sprintf(self::RELEASE_URL, $username, $candidateReleaseType, $albumSlug);
            $response = $this->httpRequest()->get($url);
            $attempts[] = $this->attempt($url, $response->status(), $response->header('Retry-After'));

            if ($response->successful()) {
                return [$url, $candidateReleaseType, $response->body()];
            }
        }

        throw new \RuntimeException(sprintf(
            'Bandcamp release details request failed [requested_type=%s attempts=%s].',
            $normalizedReleaseType,
            json_encode($attempts)
        ));
    }

    /**
     * @return array{url: string, status: int, retry_after: string}
     */
    private function attempt(string $url, int $status, ?string $retryAfter): array
    {
        return [
            'url' => $url,
            'status' => $status,
            'retry_after' => $retryAfter !== null && $retryAfter !== '' ? $retryAfter : 'n/a',
        ];
    }

    private function httpRequest(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ])->timeout(10)
            ->connectTimeout(5);
    }

    private function normalizeReleaseType(string $releaseType): ?string
    {
        return match (strtolower(trim($releaseType))) {
            'album' => 'album',
            'track', 'single' => 'track',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function preferredReleaseTypes(string $releaseType): array
    {
        if ($releaseType === 'track') {
            return ['track', 'album'];
        }

        return ['album', 'track'];
    }

    /**
     * @return array<int, array<number: int, title: ?string, length: ?string, length_seconds: ?int}>
     */
    private function tracks(Crawler $crawler): array
    {
        $tracks = [];

        $crawler->filter('.track_list .track_row_view')->each(function (Crawler $node, int $index) use (&$tracks): void {
            $length = $this->textFrom($node, '.time');

            $tracks[] = [
                'number' => $index + 1,
                'title' => $this->textFrom($node, '.track-title'),
                'length' => $length,
                'length_seconds' => $this->lengthToSeconds($length),
            ];
        });

        return $tracks;
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

    private function lengthToSeconds(?string $length): ?int
    {
        if ($length === null) {
            return null;
        }

        $parts = explode(':', trim($length));

        if (count($parts) !== 2) {
            return null;
        }

        [$minutes, $seconds] = $parts;

        if (! ctype_digit($minutes) || ! ctype_digit($seconds)) {
            return null;
        }

        return ((int) $minutes * 60) + (int) $seconds;
    }

    private function isoDurationToSeconds(mixed $duration): ?int
    {
        if (! is_string($duration) || $duration === '') {
            return null;
        }

        if (! preg_match('/^P(?:T)?(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $duration, $matches)) {
            return null;
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
        $seconds = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : 0;

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    private function secondsToClock(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }
}
