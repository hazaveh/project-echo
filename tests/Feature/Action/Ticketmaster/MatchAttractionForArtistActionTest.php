<?php

use App\Actions\Sources\Ticketmaster\MatchAttractionForArtistAction;
use App\Models\Artist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('stores a ticketmaster mapping when external links match', function () {
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
        'instagram' => 'monoofjapan',
    ]);

    $mapping = (new MatchAttractionForArtistAction)->execute($artist);

    expect($mapping)->not->toBeNull()
        ->and($mapping->provider)->toBe('ticketmaster')
        ->and($mapping->provider_artist_id)->toBe('K8vZ9179Mb7');

    $this->assertDatabaseHas('ticket_provider_mappings', [
        'artist_id' => $artist->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'K8vZ9179Mb7',
    ]);
});

it('returns null when no external links match', function () {
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
        'spotify' => 'not-a-match',
        'instagram' => 'not-a-match',
    ]);

    $mapping = (new MatchAttractionForArtistAction)->execute($artist);

    expect($mapping)->toBeNull();
});
