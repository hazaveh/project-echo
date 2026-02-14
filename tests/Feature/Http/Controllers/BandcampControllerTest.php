<?php

use Illuminate\Support\Facades\Http;

it('returns artist albums for a bandcamp username', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Bandcamp/artist-music-page.html'));

    Http::preventStrayRequests();
    Http::fake([
        'https://monoofjapan.bandcamp.com/music' => Http::response($stub, 200),
    ]);

    $response = $this->getJson('/api/bandcamp/artists/monoofjapan/albums');

    $response->assertSuccessful()
        ->assertJsonPath('artist.name', 'MONO')
        ->assertJsonPath('artist.location', 'Tokyo, Japan')
        ->assertJsonPath('bandcamp_url', 'https://monoofjapan.bandcamp.com')
        ->assertJsonPath('username', 'monoofjapan')
        ->assertJsonPath('photo', 'https://f4.bcbits.com/img/band-photo.jpg')
        ->assertJsonCount(2, 'albums')
        ->assertJsonPath('albums.0.name', 'Requiem for Hell')
        ->assertJsonPath('albums.0.type', 'album')
        ->assertJsonPath('albums.0.slug', 'requiem-for-hell')
        ->assertJsonPath('albums.0.url', 'https://monoofjapan.bandcamp.com/album/requiem-for-hell')
        ->assertJsonPath('albums.1.type', 'single')
        ->assertJsonPath('albums.1.slug', 'pilgrimage-of-the-soul')
        ->assertJsonPath('albums.1.url', 'https://monoofjapan.bandcamp.com/album/pilgrimage-of-the-soul');

    $this->getJson('/api/bandcamp/artists/monoofjapan/albums')->assertSuccessful();

    Http::assertSentCount(1);
});

it('returns album details for a bandcamp album', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Bandcamp/album-page.html'));

    Http::preventStrayRequests();
    Http::fake([
        'https://monoofjapan.bandcamp.com/album/requiem-for-hell' => Http::response($stub, 200),
    ]);

    $response = $this->getJson('/api/bandcamp/artists/monoofjapan/albums/requiem-for-hell');

    $response->assertSuccessful()
        ->assertJsonPath('album', 'Requiem for Hell')
        ->assertJsonPath('artist', 'MONO')
        ->assertJsonPath('bandcamp_url', 'https://monoofjapan.bandcamp.com/album/requiem-for-hell')
        ->assertJsonPath('username', 'monoofjapan')
        ->assertJsonPath('type', 'album')
        ->assertJsonPath('photo', 'https://f4.bcbits.com/img/album-cover.jpg')
        ->assertJsonPath('track_count', 3)
        ->assertJsonPath('tracks.0.number', 1)
        ->assertJsonPath('tracks.0.title', 'Death in Rebirth')
        ->assertJsonPath('tracks.0.length', '08:31')
        ->assertJsonPath('tracks.0.length_seconds', 511)
        ->assertJsonPath('tracks.2.number', 3)
        ->assertJsonPath('tracks.2.length_seconds', 1020)
        ->assertJsonPath('tracks.2.length', '17:00');
});

it('returns album details for a bandcamp single using release type route', function () {
    $stub = file_get_contents(base_path('tests/Stubs/Bandcamp/track-page.html'));

    Http::preventStrayRequests();
    Http::fake([
        'https://monoofjapan.bandcamp.com/track/elys-heartbeat-single-edit' => Http::response($stub, 200),
    ]);

    $response = $this->getJson('/api/bandcamp/artists/monoofjapan/albums/track/elys-heartbeat-single-edit');

    $response->assertSuccessful()
        ->assertJsonPath('album', "Ely's Heartbeat (Single Edit)")
        ->assertJsonPath('artist', 'MONO')
        ->assertJsonPath('bandcamp_url', 'https://monoofjapan.bandcamp.com/track/elys-heartbeat-single-edit')
        ->assertJsonPath('username', 'monoofjapan')
        ->assertJsonPath('type', 'single')
        ->assertJsonPath('photo', 'https://f4.bcbits.com/img/single-cover.jpg')
        ->assertJsonPath('track_count', 1)
        ->assertJsonPath('tracks.0.number', 1)
        ->assertJsonPath('tracks.0.title', "Ely's Heartbeat (Single Edit)")
        ->assertJsonPath('tracks.0.length', '05:05')
        ->assertJsonPath('tracks.0.length_seconds', 305);
});

it('returns not found when bandcamp returns rate limit response', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://monoofjapan.bandcamp.com/track/elys-heartbeat-single-edit' => Http::response('', 429, [
            'Retry-After' => '120',
        ]),
        'https://monoofjapan.bandcamp.com/album/elys-heartbeat-single-edit' => Http::response('', 429, [
            'Retry-After' => '120',
        ]),
    ]);

    $this->getJson('/api/bandcamp/artists/monoofjapan/albums/single/elys-heartbeat-single-edit')
        ->assertNotFound();
});
