<?php

use App\Http\Controllers\BandcampController;
use App\Http\Controllers\CaptureEmailAddressController;
use App\Http\Controllers\Jambase\GetArtistController;
use App\Http\Controllers\ListConcertMappedArtistPrnIdsController;
use App\Http\Controllers\SearchConcertController;
use App\Http\Middleware\AuthorizeTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => AuthorizeTokenMiddleware::class], function () {
    /** Jambase */
    Route::get('jambase/artists/spotify/{spotifyId}', GetArtistController::class);

    /** Bandcamp */
    Route::get('bandcamp/albums/{search}', [BandcampController::class, 'searchAlbums']);
    Route::get('bandcamp/artists/{username}/albums', [BandcampController::class, 'artistAlbums']);
    Route::get('bandcamp/artists/{username}/albums/{releaseType}/{albumSlug}', [BandcampController::class, 'albumDetailsByType']);
    Route::get('bandcamp/artists/{username}/albums/{albumSlug}', [BandcampController::class, 'albumDetails']);

    Route::post('captureEmail', CaptureEmailAddressController::class);

    Route::post('artists/upsert', \App\Http\Controllers\UpsertArtistController::class);

    Route::get('concerts/artists/mapped', ListConcertMappedArtistPrnIdsController::class);

    Route::get('artists/{prnArtistId}/concerts', SearchConcertController::class);
});
