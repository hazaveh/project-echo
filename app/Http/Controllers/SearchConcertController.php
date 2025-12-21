<?php

namespace App\Http\Controllers;

use App\Actions\Artists\FindArtistByPrnIdAction;
use App\Actions\Concerts\ConsolidateConcertPerformancesAction;
use App\Actions\Concerts\FetchConcertsFromProvidersAction;
use App\Actions\Concerts\ListArtistProviderMappingsAction;
use Illuminate\Http\JsonResponse;

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

        return response()->json([
            'ok' => true,
            'prn_artist_id' => $artist->prn_artist_id,
            'synced_at' => now()->toIso8601ZuluString(),
            'performances' => $performances,
        ]);
    }
}
