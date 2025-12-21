<?php

use App\Actions\Concerts\ConsolidateConcertPerformancesAction;
use App\DTO\ConcertProviders\ProviderResult;

it('generates deterministic identifiers', function () {
    $action = new ConsolidateConcertPerformancesAction;

    $result = $action->execute(123, [
        providerResult(basePerformance()),
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['identifier'])->toBe(consolidateExpectedIdentifier(123, '2026-03-12', 'DE', 'Berlin', 'Columbia Theater'));
});

it('merges offers across providers', function () {
    $action = new ConsolidateConcertPerformancesAction;

    $ticketmaster = basePerformance([
        'offers' => [
            [
                'provider' => 'ticketmaster',
                'provider_name' => 'Ticketmaster',
                'url' => 'https://ticketmaster.test',
            ],
        ],
    ]);

    $eventim = basePerformance([
        'offers' => [
            [
                'provider' => 'eventim',
                'provider_name' => 'Eventim',
                'url' => 'https://eventim.test',
            ],
        ],
    ]);

    $result = $action->execute(123, [
        providerResult($ticketmaster),
        providerResult($eventim),
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['offers'])->toHaveCount(2)
        ->and($result[0]['offers'][0]['provider'])->toBe('ticketmaster')
        ->and($result[0]['offers'][1]['provider'])->toBe('eventim');
});

it('prioritizes statuses when merging', function () {
    $action = new ConsolidateConcertPerformancesAction;

    $scheduled = basePerformance([
        'status' => 'scheduled',
    ]);

    $cancelled = basePerformance([
        'status' => 'cancelled',
    ]);

    $result = $action->execute(123, [
        providerResult($scheduled),
        providerResult($cancelled),
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['status'])->toBe('cancelled');
});

it('prefers non-null fields when consolidating', function () {
    $action = new ConsolidateConcertPerformancesAction;

    $withNulls = basePerformance([
        'address' => null,
        'latitude' => null,
    ]);

    $withValues = basePerformance([
        'address' => 'Mehringdamm 65',
        'latitude' => 52.4937,
    ]);

    $result = $action->execute(123, [
        providerResult($withNulls),
        providerResult($withValues),
    ]);

    expect($result)->toHaveCount(1)
        ->and($result[0]['address'])->toBe('Mehringdamm 65')
        ->and($result[0]['latitude'])->toBe(52.4937);
});

function providerResult(array $performance): ProviderResult
{
    return new ProviderResult(
        ok: true,
        statusCode: 200,
        payload: [],
        errorMessage: null,
        performances: [$performance]
    );
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
 *     offers: array<int, array{
 *         provider: string,
 *         provider_name: string,
 *         url: string|null
 *     }>
 * }
 */
function basePerformance(array $overrides = []): array
{
    $base = [
        'identifier' => null,
        'name' => 'MONO',
        'type' => 'concert',
        'start_date' => '2026-03-12',
        'end_date' => null,
        'status' => 'scheduled',
        'address_name' => 'Columbia Theater',
        'address' => 'Mehringdamm 65',
        'city' => 'Berlin',
        'country' => 'Germany',
        'country_code' => 'DE',
        'postal_code' => '10961',
        'latitude' => 52.4937,
        'longitude' => 13.3874,
        'offers' => [
            [
                'provider' => 'ticketmaster',
                'provider_name' => 'Ticketmaster',
                'url' => 'https://ticketmaster.test',
            ],
        ],
    ];

    return array_merge($base, $overrides);
}

function consolidateExpectedIdentifier(int $prnArtistId, string $startDate, string $countryCode, string $city, string $venue): string
{
    $normalizedCity = consolidateNormalizeValue($city);
    $normalizedVenue = consolidateNormalizeValue($venue);

    return 'echo:perf:'.sha1($prnArtistId.$startDate.$countryCode.$normalizedCity.$normalizedVenue);
}

function consolidateNormalizeValue(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    return $normalized ?? '';
}
