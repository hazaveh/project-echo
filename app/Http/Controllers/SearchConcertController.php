<?php

namespace App\Http\Controllers;

use App\Actions\Artists\FindArtistByPrnIdAction;
use App\Actions\Concerts\ConsolidateConcertPerformancesAction;
use App\Actions\Concerts\FetchConcertsFromProvidersAction;
use App\Actions\Concerts\ListArtistProviderMappingsAction;
use App\DTO\ConcertProviders\ProviderResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SearchConcertController extends Controller
{
    public function __invoke(
        int $prnArtistId,
        FindArtistByPrnIdAction $findArtistByPrnIdAction,
        ListArtistProviderMappingsAction $listArtistProviderMappingsAction,
        FetchConcertsFromProvidersAction $fetchConcertsFromProvidersAction,
        ConsolidateConcertPerformancesAction $consolidateConcertPerformancesAction
    ): JsonResponse {
        $artist = $findArtistByPrnIdAction->execute($prnArtistId);

        if (! $artist) {
            return response()->json([
                'ok' => false,
                'error' => 'artist_not_found',
                'message' => 'Artist not found.',
            ], 404);
        }

        $cacheKey = $this->cacheKey($artist->prn_artist_id);
        $cachedResponse = Cache::get($cacheKey);

        if (is_array($cachedResponse)) {
            return response()->json($cachedResponse);
        }

        $mappings = $listArtistProviderMappingsAction->execute($artist);

        if ($mappings->isEmpty()) {
            return response()->json([
                'ok' => true,
                'prn_artist_id' => $artist->prn_artist_id,
                'synced_at' => now()->toIso8601ZuluString(),
                'performances' => [],
            ]);
        }

        $providerResults = $fetchConcertsFromProvidersAction->execute($artist, $mappings);
        $performances = $consolidateConcertPerformancesAction->execute($artist->prn_artist_id, $providerResults);

        $response = [
            'ok' => true,
            'prn_artist_id' => $artist->prn_artist_id,
            'synced_at' => now()->toIso8601ZuluString(),
            'performances' => $performances,
        ];

        if ($this->shouldCacheResponse($providerResults)) {
            Cache::put($cacheKey, $response, 60);
        }

        return response()->json($response);
    }

    private function cacheKey(int $prnArtistId): string
    {
        return 'concerts-search-'.$prnArtistId;
    }

    /**
     * @param  array<int, ProviderResult>  $providerResults
     */
    private function shouldCacheResponse(array $providerResults): bool
    {
        foreach ($providerResults as $result) {
            if (! $result->ok) {
                return false;
            }
        }

        return true;
    }
}
