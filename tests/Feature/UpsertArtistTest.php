<?php

use App\Models\Artist;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an artist from the upsert payload', function () {
    $payload = [
        'prn_artist_id' => 123,
        'name' => 'we.own.the.sky',
        'spotify' => '2uFUBdaVGtyMqckSeCl0Qj',
        'instagram' => 'weownthesky',
        'twitter' => null,
        'facebook' => null,
        'homepage' => 'weownthesky.com',
        'apple' => 'apple-id',
        'youtube' => null,
    ];

    $response = $this->postJson('/api/artists/upsert', $payload);

    $response->assertSuccessful()
        ->assertJsonPath('artist.prn_artist_id', 123)
        ->assertJsonPath('artist.spotify', '2uFUBdaVGtyMqckSeCl0Qj')
        ->assertJsonPath('artist.apple_music', 'apple-id');

    $this->assertDatabaseHas('artists', [
        'prn_artist_id' => 123,
        'name' => 'we.own.the.sky',
        'spotify' => '2uFUBdaVGtyMqckSeCl0Qj',
        'instagram' => 'weownthesky',
        'twitter' => null,
        'facebook' => null,
        'homepage' => 'weownthesky.com',
        'apple_music' => 'apple-id',
        'youtube' => null,
    ]);
});

it('updates an artist when the prn artist id already exists', function () {
    $artist = Artist::factory()->create([
        'prn_artist_id' => 456,
        'name' => 'Old Name',
        'spotify' => 'aaaaaaaaaaaaaaaaaaaaaa',
    ]);

    $payload = [
        'prn_artist_id' => 456,
        'name' => 'New Name',
        'spotify' => 'bbbbbbbbbbbbbbbbbbbbbb',
        'instagram' => 'newhandle',
        'twitter' => 'newtwitter',
        'facebook' => null,
        'homepage' => 'example.com',
        'apple' => 'apple-id',
        'youtube' => 'yt-id',
    ];

    $response = $this->postJson('/api/artists/upsert', $payload);

    $response->assertSuccessful()
        ->assertJsonPath('artist.id', $artist->id)
        ->assertJsonPath('artist.name', 'New Name');

    expect(Artist::where('prn_artist_id', 456)->count())->toBe(1);

    $this->assertDatabaseHas('artists', [
        'id' => $artist->id,
        'prn_artist_id' => 456,
        'name' => 'New Name',
        'spotify' => 'bbbbbbbbbbbbbbbbbbbbbb',
        'instagram' => 'newhandle',
        'twitter' => 'newtwitter',
        'homepage' => 'example.com',
        'apple_music' => 'apple-id',
        'youtube' => 'yt-id',
    ]);
});

it('validates required fields', function (array $payload, array $errors) {
    $response = $this->postJson('/api/artists/upsert', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing prn_artist_id' => [
        [
            'name' => 'Artist Name',
            'spotify' => '2uFUBdaVGtyMqckSeCl0Qj',
        ],
        ['prn_artist_id'],
    ],
    'missing name' => [
        [
            'prn_artist_id' => 123,
        ],
        ['name'],
    ],
]);
