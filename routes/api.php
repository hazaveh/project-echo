<?php

use App\Http\Controllers\BandcampController;
use App\Http\Controllers\CaptureEmailAddressController;
use App\Http\Controllers\Concerts\GetTickermasterEventsController;
use App\Http\Controllers\Jambase\GetArtistController;
use App\Http\Middleware\AuthorizeTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => AuthorizeTokenMiddleware::class], function () {
    /** Jambase */
    Route::get('jambase/artists/spotify/{spotifyId}', GetArtistController::class);

    /** Bandcamp */
    Route::get('bandcamp/albums/{search}', [BandcampController::class, 'searchAlbums']);

    Route::post('captureEmail', CaptureEmailAddressController::class);

    Route::post('artists/upsert', \App\Http\Controllers\UpsertArtistController::class);

    Route::get('artists/concerts/ticketmaster/{prnArtistId}', GetTickermasterEventsController::class);
});
