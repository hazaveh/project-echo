<?php

namespace App\DTO\Response\Artist;

class Event
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $type,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly string $status,
        public readonly EventAddress $address
    ) {}

    public static function fromJambaseEvent($event): self
    {
        $address = EventAddress::fromJamvaseEventLocation($event['location']);

        return new self(
            id: $event['identifier'],
            name: $event['name'],
            type: $event['@type'],
            startDate: $event['startDate'],
            endDate: $event['endDate'],
            status: $event['eventStatus'],
            address: $address
        );
    }
}
