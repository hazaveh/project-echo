<?php

namespace App\Services\ConcertProviders;

use App\DTO\ConcertProviders\ProviderResult;
use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Support\Facades\Http;

class TicketmasterConcertProvider implements ConcertProviderInterface
{
    public function providerKey(): string
    {
        return 'ticketmaster';
    }

    public function providerName(): string
    {
        return 'Ticketmaster';
    }

    public function supports(TicketProviderMapping $mapping): bool
    {
        return $mapping->provider === $this->providerKey();
    }

    public function fetchConcerts(Artist $artist, TicketProviderMapping $mapping): ProviderResult
    {
        $response = Http::baseUrl(config('services.ticketmaster.base_url'))
            ->get('events', [
                'apikey' => config('services.ticketmaster.key'),
                'attractionId' => $mapping->provider_artist_id,
                'classificationName' => 'music',
                'sort' => 'date,asc',
                'size' => 100,
                'locale' => '*',
            ]);

        $payload = $response->json();
        $payload = is_array($payload) ? $payload : null;

        if ($response->failed()) {
            return new ProviderResult(
                ok: false,
                statusCode: $response->status(),
                payload: $payload,
                errorMessage: 'Ticketmaster request failed.',
                performances: []
            );
        }

        return new ProviderResult(
            ok: true,
            statusCode: $response->status(),
            payload: $payload,
            errorMessage: null,
            performances: $this->performancesFromTicketmasterResponse($payload ?? [])
        );
    }

    /**
     * @return array<int, array{
     *     identifier: string|null,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }>
     */
    private function performancesFromTicketmasterResponse(array $payload): array
    {
        $events = data_get($payload, '_embedded.events', []);

        if (! is_array($events)) {
            return [];
        }

        $performances = [];

        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $performances[] = $this->performanceFromTicketmasterEvent($event);
        }

        return $performances;
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{
     *     identifier: string|null,
     *     name: string|null,
     *     type: string,
     *     start_date: string|null,
     *     end_date: string|null,
     *     status: string,
     *     address_name: string|null,
     *     address: string|null,
     *     city: string|null,
     *     country: string|null,
     *     country_code: string|null,
     *     postal_code: string|null,
     *     latitude: float|null,
     *     longitude: float|null,
     *     offers: array<int, array{
     *         provider: string,
     *         provider_name: string,
     *         url: string|null
     *     }>
     * }
     */
    private function performanceFromTicketmasterEvent(array $event): array
    {
        $venue = data_get($event, '_embedded.venues.0', []);
        $ticketUrl = $this->stringOrNull(data_get($event, 'url'));
        $status = $this->stringOrNull(data_get($event, 'dates.status.code')) ?? 'scheduled';

        return [
            'identifier' => null,
            'name' => $this->stringOrNull(data_get($event, 'name')),
            'type' => 'concert',
            'start_date' => $this->stringOrNull(data_get($event, 'dates.start.localDate')),
            'end_date' => $this->stringOrNull(data_get($event, 'dates.end.localDate')),
            'status' => $status,
            'address_name' => $this->stringOrNull(data_get($venue, 'name')),
            'address' => $this->stringOrNull(data_get($venue, 'address.line1')),
            'city' => $this->stringOrNull(data_get($venue, 'city.name')),
            'country' => $this->stringOrNull(data_get($venue, 'country.name')),
            'country_code' => $this->stringOrNull(data_get($venue, 'country.countryCode')),
            'postal_code' => $this->stringOrNull(data_get($venue, 'postalCode')),
            'latitude' => $this->coordinateOrNull(data_get($venue, 'location.latitude')),
            'longitude' => $this->coordinateOrNull(data_get($venue, 'location.longitude')),
            'offers' => [
                [
                    'provider' => $this->providerKey(),
                    'provider_name' => $this->providerName(),
                    'url' => $this->affiliateUrl($ticketUrl),
                ],
            ],
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function coordinateOrNull(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function affiliateUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $template = config('services.ticketmaster.affiliate_url');

        if (! is_string($template) || $template === '') {
            return $url;
        }

        return str_replace('{url}', urlencode($url), $template);
    }
}
