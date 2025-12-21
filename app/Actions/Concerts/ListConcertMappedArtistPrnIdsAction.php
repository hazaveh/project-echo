<?php

namespace App\Actions\Concerts;

use App\Models\Artist;
use Illuminate\Support\Collection;

class ListConcertMappedArtistPrnIdsAction
{
    /**
     * @return Collection<int, int>
     */
    public function execute(): Collection
    {
        return Artist::query()
            ->whereHas('ticketProviderMappings')
            ->orderBy('prn_artist_id')
            ->pluck('prn_artist_id')
            ->map(static fn ($prnArtistId) => (int) $prnArtistId)
            ->values();
    }
}
