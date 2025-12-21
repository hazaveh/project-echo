<?php

use App\Models\Artist;
use App\Models\ConcertProviderSyncLog;
use App\Models\TicketProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('returns json 404 when the artist does not exist', function () {
    $response = $this->getJson('/api/artists/999/concerts');

    $response->assertNotFound()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('error', 'artist_not_found')
        ->assertJsonPath('message', 'Artist not found.');
});

it('returns empty performances when no provider mappings exist', function () {
    $artist = Artist::factory()->create([
        'prn_artist_id' => 456,
        'name' => 'MONO',
    ]);

    $response = $this->getJson('/api/artists/'.$artist->prn_artist_id.'/concerts');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('prn_artist_id', 456)
        ->assertJsonCount(0, 'performances');
});

it('returns ticketmaster concerts for an artist', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/events-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    $callCount = 0;

    Http::fake(function () use (&$callCount, $stub) {
        $callCount++;

        return $callCount === 1
            ? Http::response($stub, 200)
            : Http::response(['error' => 'bad'], 500);
    });

    $artist = Artist::factory()->create([
        'prn_artist_id' => 123,
        'name' => 'MONO',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9175Tr0',
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/artists/123/concerts');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('prn_artist_id', 123)
        ->assertJsonCount(2, 'performances')
        ->assertJsonPath('performances.0.address_name', 'Columbia Theater')
        ->assertJsonPath('performances.0.latitude', 52.4937)
        ->assertJsonPath('performances.0.offers.0.provider', 'ticketmaster')
        ->assertJsonPath('performances.0.offers.0.provider_name', 'Ticketmaster')
        ->assertJsonPath('performances.1.offers.0.url', null)
        ->assertJsonPath('performances.0.identifier', concertIdentifier(123, '2026-03-12', 'DE', 'Berlin', 'Columbia Theater'))
        ->assertJsonPath('performances.1.identifier', concertIdentifier(123, '2026-03-14', 'DE', 'Cologne', 'Live Music Hall'));

    $cachedResponse = $this->getJson('/api/artists/123/concerts');

    $cachedResponse->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonCount(2, 'performances');

    expect($callCount)->toBe(1);
});

it('returns json even when the provider fails', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/events-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    $callCount = 0;

    Http::fake(function () use (&$callCount, $stub) {
        $callCount++;

        return $callCount === 1
            ? Http::response(['error' => 'bad'], 500)
            : Http::response($stub, 200);
    });

    $artist = Artist::factory()->create([
        'prn_artist_id' => 321,
        'name' => 'MONO',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9175Tr0',
        'status' => 'active',
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($artist) {
            return $message === 'Ticketmaster request failed.'
                && ($context['prn_artist_id'] ?? null) === $artist->prn_artist_id
                && ($context['artist_id'] ?? null) === $artist->id
                && ($context['provider_artist_id'] ?? null) === 'K8vZ9175Tr0'
                && ($context['status'] ?? null) === 500;
        });

    $response = $this->getJson('/api/artists/321/concerts');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonCount(0, 'performances');

    $secondResponse = $this->getJson('/api/artists/321/concerts');

    $secondResponse->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonCount(2, 'performances');

    expect($callCount)->toBe(2);
});

it('writes sync logs for successful provider responses', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/events-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/events*' => Http::response($stub, 200),
    ]);

    $artist = Artist::factory()->create([
        'prn_artist_id' => 555,
        'name' => 'MONO',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9175Tr0',
        'status' => 'active',
    ]);

    $this->getJson('/api/artists/555/concerts')->assertSuccessful();

    $successLog = ConcertProviderSyncLog::query()->first();

    expect($successLog)->not->toBeNull()
        ->and($successLog->ok)->toBeTrue()
        ->and($successLog->status_code)->toBe(200)
        ->and($successLog->provider)->toBe('ticketmaster')
        ->and($successLog->result_count)->toBe(2);
});

it('writes sync logs for provider errors', function () {
    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/events*' => Http::response(['error' => 'bad'], 500),
    ]);

    $artist = Artist::factory()->create([
        'prn_artist_id' => 777,
        'name' => 'MONO',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9175Tr0',
        'status' => 'active',
    ]);

    $this->getJson('/api/artists/777/concerts')->assertSuccessful();

    $errorLog = ConcertProviderSyncLog::query()->first();

    expect($errorLog)->not->toBeNull()
        ->and($errorLog->ok)->toBeFalse()
        ->and($errorLog->status_code)->toBe(500);
});

function concertIdentifier(int $prnArtistId, string $startDate, string $countryCode, string $city, string $venue): string
{
    $normalizedCity = concertNormalizeValue($city);
    $normalizedVenue = concertNormalizeValue($venue);

    return 'echo:perf:'.sha1($prnArtistId.$startDate.$countryCode.$normalizedCity.$normalizedVenue);
}

function concertNormalizeValue(string $value): string
{
    $normalized = strtolower(trim($value));
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    return $normalized ?? '';
}
