<?php

namespace App\DTO\Response\Artist;

class EventAddress
{
    public function __construct(
        public readonly string $name,
        public readonly string $streetAddress,
        public readonly string $locality,
        public readonly string $postalCode,
        public readonly string $country,
        public readonly string $countryCode,
        public readonly string $lat,
        public readonly string $lng
    ) {}

    public static function fromJamvaseEventLocation($location): self
    {
        return new self(
            name: $location['name'],
            streetAddress: $location['address']['streetAddress'],
            locality: $location['address']['addressLocality'],
            postalCode: $location['address']['postalCode'],
            country: $location['address']['addressCountry']['name'],
            lat: $location['geo']['latitude'],
            lng: $location['geo']['longitude'],
            countryCode: $location['address']['addressCountry']['identifier']
        );
    }
}
