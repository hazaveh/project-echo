<?php

namespace App\DTO\Response\Jambase;

use App\DTO\Response\Artist\Event;

class SearchArtistDTO implements \JsonSerializable
{
    public function __construct(
        private string $name,
        private string $identifier,
        private array $events
    ) {}

    public static function fromResponse($response): SearchArtistDTO
    {
        $events = array_map(static fn ($event) => Event::fromJambaseEvent($event), $response['events']);

        return new self(
            name: $response['name'],
            identifier: $response['identifier'],
            events: $events
        );
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
