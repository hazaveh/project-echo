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
