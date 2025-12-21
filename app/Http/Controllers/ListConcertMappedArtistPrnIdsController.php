<?php

namespace App\Http\Controllers;

use App\Actions\Concerts\ListConcertMappedArtistPrnIdsAction;
use Illuminate\Http\JsonResponse;

class ListConcertMappedArtistPrnIdsController extends Controller
{
    public function __invoke(ListConcertMappedArtistPrnIdsAction $listConcertMappedArtistPrnIdsAction): JsonResponse
    {
        $prnArtistIds = $listConcertMappedArtistPrnIdsAction->execute();

        return response()->json([
            'ok' => true,
            'count' => $prnArtistIds->count(),
            'prn_artist_ids' => $prnArtistIds,
        ]);
    }
}
