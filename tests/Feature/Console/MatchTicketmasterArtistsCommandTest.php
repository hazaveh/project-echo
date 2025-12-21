<?php

use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('matches ticketmaster attractions for artists without mappings', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/attractions-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/attractions*' => Http::response($stub, 200),
    ]);

    $match = Artist::factory()->create([
        'name' => 'MONO',
        'spotify' => '53LVoipNTQ4lvUSJ61XKU3',
    ]);

    Artist::factory()->create([
        'name' => 'MONO',
        'spotify' => 'not-a-match',
    ]);

    $this->artisan('ticketmaster:match-artists', ['--sleep' => 0])->assertExitCode(0);

    expect(TicketProviderMapping::count())->toBe(1);

    $this->assertDatabaseHas('ticket_provider_mappings', [
        'artist_id' => $match->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9179Mb7',
    ]);
});

it('skips artists with recent ticketmaster match attempts', function () {
    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake();

    $attemptedAt = now()->subHours(2);
    $artist = Artist::factory()->create([
        'name' => 'MONO',
        'spotify' => '53LVoipNTQ4lvUSJ61XKU3',
        'ticketmaster_match_attempted_at' => $attemptedAt,
    ]);

    $this->artisan('ticketmaster:match-artists', ['--sleep' => 0])->assertExitCode(0);

    expect(TicketProviderMapping::count())->toBe(0);

    $artist->refresh();

    expect($artist->ticketmaster_match_attempted_at?->format('Y-m-d H:i:s'))
        ->toBe($attemptedAt->format('Y-m-d H:i:s'));

    Http::assertNothingSent();
});

it('retries artists with stale ticketmaster mappings', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Ticketmaster/attractions-search-mono'));

    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/attractions*' => Http::response($stub, 200),
    ]);

    $artist = Artist::factory()->create([
        'name' => 'MONO',
        'spotify' => '53LVoipNTQ4lvUSJ61XKU3',
    ]);

    $staleTimestamp = now()->subDays(31);

    $mapping = TicketProviderMapping::create([
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'stale-id',
        'status' => 'active',
        'last_synced_at' => $staleTimestamp,
    ]);

    $this->artisan('ticketmaster:match-artists', ['--sleep' => 0])->assertExitCode(0);

    $mapping->refresh();

    expect($mapping->provider_artist_id)->toBe('K8vZ9179Mb7');
    expect($mapping->last_synced_at->greaterThan($staleTimestamp))->toBeTrue();
});

it('does not record attempts when ticketmaster requests fail', function () {
    config([
        'services.ticketmaster.base_url' => 'https://app.ticketmaster.com/discovery/v2',
        'services.ticketmaster.key' => 'test-key',
    ]);

    Http::fake([
        'https://app.ticketmaster.com/discovery/v2/attractions*' => Http::response(['message' => 'rate limited'], 429),
    ]);

    $artist = Artist::factory()->create([
        'name' => 'MONO',
        'spotify' => '53LVoipNTQ4lvUSJ61XKU3',
    ]);

    $this->artisan('ticketmaster:match-artists', ['--sleep' => 0])->assertExitCode(0);

    $artist->refresh();

    expect($artist->ticketmaster_match_attempted_at)->toBeNull();
    expect(TicketProviderMapping::count())->toBe(0);
});
