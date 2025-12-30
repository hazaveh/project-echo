<?php

namespace App\Actions\Geo;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeocodeVenueAction
{
    private const BASE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * @return array{address: string|null, country: string|null, country_code: string|null}|null
     */
    public function execute(string $venueName, ?string $postalCode, ?string $city, ?string $country): ?array
    {
        $apiKey = config('services.google.geocoding_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $address = $this->addressQuery($venueName, $postalCode, $city, $country);

        if ($address === null) {
            Log::warning('Google geocoding address query could not be built.', [
                'venue_name' => $venueName,
                'postal_code' => $postalCode,
                'city' => $city,
                'country' => $country,
            ]);

            return null;
        }

        try {
            $response = Http::timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->get(self::BASE_URL, [
                    'address' => $address,
                    'key' => $apiKey,
                ]);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Google geocoding request threw an exception.', [
                'address' => $address,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('Google geocoding request failed.', [
                'address' => $address,
                'status' => $response->status(),
                'payload' => $response->json(),
            ]);

            return null;
        }

        $data = $response->json();

        $status = is_array($data) ? data_get($data, 'status') : null;

        if (! is_array($data) || $status !== 'OK') {
            Log::warning('Google geocoding returned no results.', [
                'address' => $address,
                'status' => $status,
                'error_message' => is_array($data) ? data_get($data, 'error_message') : null,
                'response' => $data,
            ]);

            return null;
        }

        $result = data_get($data, 'results.0', []);

        if (! is_array($result)) {
            Log::warning('Google geocoding response was missing results.', [
                'address' => $address,
                'response' => $data,
            ]);

            return null;
        }

        $components = data_get($result, 'address_components', []);
        $components = is_array($components) ? $components : [];

        $country = $this->countryFromComponents($components);

        $resolvedAddress = $this->streetAddressFromComponents($components)
            ?? $this->stringOrNull(data_get($result, 'formatted_address'));

        if ($resolvedAddress === null) {
            Log::warning('Google geocoding did not return a usable address.', [
                'address' => $address,
                'response' => $result,
            ]);
        }

        return [
            'address' => $resolvedAddress,
            'country' => $country['name'],
            'country_code' => $country['code'],
        ];
    }

    private function addressQuery(string $venueName, ?string $postalCode, ?string $city, ?string $country): ?string
    {
        $parts = array_filter([
            $this->trimmed($venueName),
            $this->trimmed($postalCode),
            $this->trimmed($city),
            $this->trimmed($country),
        ]);

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     * @return array{code: string|null, name: string|null}
     */
    private function countryFromComponents(array $components): array
    {
        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $types = $component['types'] ?? null;

            if (! is_array($types) || ! in_array('country', $types, true)) {
                continue;
            }

            $name = $this->stringOrNull($component['long_name'] ?? null);
            $code = $this->stringOrNull($component['short_name'] ?? null);

            return [
                'name' => $name,
                'code' => $code !== null ? strtoupper($code) : null,
            ];
        }

        return ['name' => null, 'code' => null];
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    private function streetAddressFromComponents(array $components): ?string
    {
        $route = null;
        $streetNumber = null;

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $types = $component['types'] ?? null;

            if (! is_array($types)) {
                continue;
            }

            if (in_array('route', $types, true)) {
                $route = $this->stringOrNull($component['long_name'] ?? null);
            }

            if (in_array('street_number', $types, true)) {
                $streetNumber = $this->stringOrNull($component['long_name'] ?? null);
            }
        }

        $parts = array_filter([$route, $streetNumber]);

        if ($parts === []) {
            return null;
        }

        return implode(' ', $parts);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function trimmed(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
