<?php

namespace App\Services\ConcertProviders;

use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\TicketProviderMapping;

interface ConcertProviderInterface
{
    public function providerKey(): string;

    public function providerName(): string;

    public function supports(TicketProviderMapping $mapping): bool;

    public function fetchConcerts(Artist $artist, TicketProviderMapping $mapping): ProviderResult;
}
