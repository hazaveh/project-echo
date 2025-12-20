<?php

namespace App\Http\Controllers;

use App\Enum\TicketProvidersEnum;
use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SearchConcertController extends Controller
{
    public function __invoke(TicketProvidersEnum $provider, int $prnId): JsonResponse
    {
        if ($provider !== TicketProvidersEnum::TICKETMASTER) {
            return response()->json([
                'ok' => false,
                'message' => 'Provider not supported.',
            ], 422);
        }

        $artist = Artist::findByPrnArtistId($prnId);

        if (! $artist) {
            return response()->json([
                'ok' => false,
                'message' => 'Artist not found.',
            ], 404);
        }

        $mapping = $artist->ticketProvider($provider->value);

        if (! $mapping) {
            return response()->json([
                'ok' => false,
                'message' => 'Ticketmaster mapping not found.',
            ], 404);
        }

        $response = $this->fetchTicketmasterEvents($mapping);

        if ($response->failed()) {
            return response()->json([
                'ok' => false,
                'message' => 'Ticketmaster request failed.',
            ], $response->status());
        }

        return response()->json([
            'ok' => true,
            'provider' => $provider->value,
            'prn_artist_id' => $artist->prn_artist_id,
            'provider_artist_id' => $mapping->provider_artist_id,
            'synced_at' => $mapping->last_synced_at?->toIso8601ZuluString(),
            'performances' => $this->performancesFromTicketmasterResponse($response->json()),
        ]);
    }

    private function fetchTicketmasterEvents(TicketProviderMapping $mapping): Response
    {
        return Http::baseUrl(config('services.ticketmaster.base_url'))
            ->get('events', [
                'apikey' => config('services.ticketmaster.key'),
                'attractionId' => $mapping->provider_artist_id,
                'classificationName' => 'music',
                'sort' => 'date,asc',
                'size' => 100,
            ]);
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
     *     ticket_url: string|null
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
     *     ticket_url: string|null
     * }
     */
    private function performanceFromTicketmasterEvent(array $event): array
    {
        $venue = data_get($event, '_embedded.venues.0', []);
        $eventId = $this->stringOrNull(data_get($event, 'id'));
        $ticketUrl = $this->stringOrNull(data_get($event, 'url'));
        $status = $this->stringOrNull(data_get($event, 'dates.status.code')) ?? 'scheduled';

        return [
            'identifier' => $eventId ? sprintf('echo:tm:%s', $eventId) : null,
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
            'ticket_url' => $ticketUrl,
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
}
