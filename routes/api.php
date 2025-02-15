<?php

use App\Http\Controllers\Jambase\GetArtistController;
use App\Http\Middleware\AuthorizeTokenMiddleware;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => AuthorizeTokenMiddleware::class], function () {
    Route::get('jambase/artists/spotify/{spotifyId}', GetArtistController::class);
});
