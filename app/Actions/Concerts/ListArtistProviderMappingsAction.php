<?php

namespace App\Actions\Concerts;

use App\Models\Artist;
use App\Services\ConcertProviders\ConcertProviderRegistry;
use Illuminate\Support\Collection;

class ListArtistProviderMappingsAction
{
    public function __construct(private ConcertProviderRegistry $concertProviderRegistry) {}

    public function execute(Artist $artist): Collection
    {
        $providerKeys = collect($this->concertProviderRegistry->all())
            ->map(static fn ($provider) => $provider->providerKey())
            ->values()
            ->all();

        if ($providerKeys === []) {
            return collect();
        }

        return $artist->ticketProviderMappings()
            ->whereIn('provider', $providerKeys)
            ->get();
    }
}
