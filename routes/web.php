<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    \Illuminate\Support\Facades\Log::info('someone is visiting echo!');

    return redirect('https://postrocknation.com');
});
