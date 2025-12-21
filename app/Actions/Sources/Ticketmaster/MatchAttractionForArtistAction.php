<?php

namespace App\Actions\Sources\Ticketmaster;

use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MatchAttractionForArtistAction
{
    public function execute(Artist $artist): ?TicketProviderMapping
    {
        $tokens = $this->artistTokens($artist);

        if ($tokens === []) {
            return null;
        }

        $response = Http::baseUrl(config('services.ticketmaster.base_url'))
            ->get('attractions', [
                'apikey' => config('services.ticketmaster.key'),
                'keyword' => $artist->name,
                'classificationName' => 'music',
                'size' => 20,
            ]);

        if ($response->failed()) {
            return null;
        }

        $attractions = data_get($response->json(), '_embedded.attractions', []);

        if ($attractions === []) {
            return null;
        }

        $match = $this->bestMatch($attractions, $tokens);

        if ($match === null) {
            return null;
        }

        $matchedTokens = $match['matched_tokens'];
        $resolutionMethod = in_array($this->normalizeToken($artist->spotify), $matchedTokens, true)
            ? 'spotify_verified'
            : 'name_match';

        return TicketProviderMapping::updateOrCreate(
            [
                'artist_id' => $artist->id,
                'provider' => 'ticketmaster',
            ],
            [
                'provider_artist_id' => $match['attraction']['id'],
                'status' => 'active',
                'confidence' => $this->confidence($matchedTokens, $tokens),
                'resolution_method' => $resolutionMethod,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    private function artistTokens(Artist $artist): array
    {
        $tokens = [
            $artist->spotify,
            $artist->instagram,
            $artist->twitter,
            $artist->facebook,
            $artist->youtube,
            $artist->homepage,
            $artist->apple_music,
        ];

        $normalized = [];

        foreach ($tokens as $token) {
            if (! is_string($token) || $token === '') {
                continue;
            }

            $normalizedToken = $this->normalizeToken($token);

            if ($normalizedToken !== '') {
                $normalized[] = $normalizedToken;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param  array<int, array<string, mixed>>  $attractions
     * @param  array<int, string>  $tokens
     * @return array{attraction: array<string, mixed>, matched_tokens: array<int, string>}|null
     */
    private function bestMatch(array $attractions, array $tokens): ?array
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($attractions as $attraction) {
            $candidates = $this->candidateStrings($attraction);
            $matchedTokens = $this->matchedTokens($tokens, $candidates);
            $score = count($matchedTokens);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = [
                    'attraction' => $attraction,
                    'matched_tokens' => $matchedTokens,
                ];
            }
        }

        return $bestMatch;
    }

    /**
     * @param  array<string, mixed>  $attraction
     * @return array<int, string>
     */
    private function candidateStrings(array $attraction): array
    {
        $candidates = [];
        $externalLinks = $attraction['externalLinks'] ?? [];

        if (! is_array($externalLinks)) {
            return [];
        }

        foreach ($externalLinks as $links) {
            if (! is_array($links)) {
                continue;
            }

            foreach ($links as $link) {
                if (! is_array($link)) {
                    continue;
                }

                foreach (['url', 'id'] as $key) {
                    $value = $link[$key] ?? null;

                    if (is_string($value) && $value !== '') {
                        $candidates[] = $value;
                    }
                }
            }
        }

        return $candidates;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<int, string>  $candidates
     * @return array<int, string>
     */
    private function matchedTokens(array $tokens, array $candidates): array
    {
        $matched = [];

        foreach ($tokens as $token) {
            foreach ($candidates as $candidate) {
                $normalizedCandidate = $this->normalizeToken($candidate);

                if ($normalizedCandidate === '') {
                    continue;
                }

                if (Str::contains($normalizedCandidate, $token)) {
                    $matched[] = $token;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * @param  array<int, string>  $matchedTokens
     * @param  array<int, string>  $tokens
     */
    private function confidence(array $matchedTokens, array $tokens): ?float
    {
        if ($tokens === []) {
            return null;
        }

        return count($matchedTokens) / count($tokens);
    }

    private function normalizeToken(?string $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $normalized = Str::lower(trim($value));
        $normalized = preg_replace('/^https?:\/\//', '', $normalized);
        $normalized = preg_replace('/^www\./', '', $normalized);
        $normalized = ltrim($normalized, '@');

        return $normalized ?? '';
    }
}
