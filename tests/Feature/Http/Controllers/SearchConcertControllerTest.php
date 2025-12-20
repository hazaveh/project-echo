<?php

use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('returns ticketmaster concerts for an artist', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/events-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/events*' => Http::response($stub, 200),
    ]);

    $artist = Artist::factory()->create([
        'prn_artist_id' => 123,
        'name' => 'MONO',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9175Tr0',
        'status' => 'active',
        'last_synced_at' => Carbon::parse('2025-12-20T16:25:00Z')->utc(),
    ]);

    $response = $this->getJson('/api/artists/concerts/ticketmaster/123');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('provider', 'ticketmaster')
        ->assertJsonPath('prn_artist_id', 123)
        ->assertJsonPath('provider_artist_id', 'K8vZ9175Tr0')
        ->assertJsonPath('synced_at', '2025-12-20T16:25:00Z')
        ->assertJsonCount(2, 'performances')
        ->assertJsonPath('performances.0.identifier', 'echo:tm:Z7r9jZ1A7a1oF')
        ->assertJsonPath('performances.0.address_name', 'Columbia Theater')
        ->assertJsonPath('performances.0.latitude', 52.4937)
        ->assertJsonPath('performances.1.identifier', 'echo:tm:G5vYZ9l2k3mPq')
        ->assertJsonPath('performances.1.ticket_url', null);
});

it('returns not found when the artist does not exist', function () {
    $response = $this->getJson('/api/artists/concerts/ticketmaster/999');

    $response->assertNotFound()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('message', 'Artist not found.');
});

it('returns not found when the ticketmaster mapping is missing', function () {
    Artist::factory()->create([
        'prn_artist_id' => 456,
        'name' => 'MONO',
    ]);

    $response = $this->getJson('/api/artists/concerts/ticketmaster/456');

    $response->assertNotFound()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('message', 'Ticketmaster mapping not found.');
});

it('returns unprocessable when the provider is unsupported', function () {
    $response = $this->getJson('/api/artists/concerts/eventbrite/123');

    $response->assertUnprocessable()
        ->assertJsonPath('ok', false)
        ->assertJsonPath('message', 'Provider not supported.');
});
