<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class HttpClientProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->jambase();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    private function jambase(): void
    {
        Http::macro('jambase', function () {
            return Http::baseUrl(config('services.jambase.base_url'))->withQueryParameters(['apikey' => config('services.jambase.key')]);
        });
    }
}
