<?php

namespace App\Services\ConcertProviders;

use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\TicketProviderMapping;

class EventimConcertProvider implements ConcertProviderInterface
{
    public function providerKey(): string
    {
        return 'eventim';
    }

    public function providerName(): string
    {
        return 'Eventim';
    }

    public function supports(TicketProviderMapping $mapping): bool
    {
        return $mapping->provider === $this->providerKey();
    }

    public function fetchConcerts(Artist $artist, TicketProviderMapping $mapping): ProviderResult
    {
        return new ProviderResult(
            ok: false,
            statusCode: null,
            payload: null,
            errorMessage: 'Not implemented',
            performances: []
        );
    }
}
