<?php

namespace App\DTO\Ticketmaster;

use App\Models\TicketProviderMapping;

class MatchAttractionResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?TicketProviderMapping $mapping,
    ) {}
}
