<?php

namespace App\Enum;

enum TicketProvidersEnum: string
{
    case TICKETMASTER = 'ticketmaster';
    case EVENTBRITE = 'eventbrite';
    case SEETICKETS = 'seetickets';
}
