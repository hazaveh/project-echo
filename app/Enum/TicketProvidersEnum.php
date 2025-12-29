<?php

namespace App\Enum;

enum TicketProvidersEnum: string
{
    case TICKETMASTER = 'ticketmaster';
    case EVENTBRITE = 'eventbrite';
    case SEETICKETS = 'seetickets';

    public static function filamentSelectOptions(): array
    {
        return array_combine(
            array_map(fn ($provider) => $provider->value, self::cases()),
            array_map(fn ($provider) => ucfirst($provider->value), self::cases())
        );
    }
}
