<?php

use App\Models\Artist;
use App\Models\TicketProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns mapped artist prn ids', function () {
    $first = Artist::factory()->create([
        'prn_artist_id' => 101,
        'name' => 'First Artist',
    ]);

    $second = Artist::factory()->create([
        'prn_artist_id' => 202,
        'name' => 'Second Artist',
    ]);

    Artist::factory()->create([
        'prn_artist_id' => 303,
        'name' => 'Unmapped Artist',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $first->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'tm-1',
        'status' => 'active',
    ]);

    TicketProviderMapping::create([
        'artist_id' => $second->id,
        'provider' => 'ticketmaster',
        'provider_artist_id' => 'tm-2',
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/concerts/artists/mapped');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('count', 2)
        ->assertJsonCount(2, 'prn_artist_ids')
        ->assertJsonPath('prn_artist_ids.0', 101)
        ->assertJsonPath('prn_artist_ids.1', 202);
});

it('returns an empty list when no mappings exist', function () {
    Artist::factory()->create([
        'prn_artist_id' => 404,
        'name' => 'No Mapping Artist',
    ]);

    $response = $this->getJson('/api/concerts/artists/mapped');

    $response->assertSuccessful()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('count', 0)
        ->assertJsonCount(0, 'prn_artist_ids');
});
