<?php

namespace App\Providers;

use App\Actions\Reddit\GetAccessToken;
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
        $this->reddit();
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

    private function reddit(): void
    {
        Http::macro('reddit', function () {
            return Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
            ])->withToken((new GetAccessToken)->execute())->asForm();
        });
    }

    private function ticketmaster(): void
    {
        Http::macro('ticketmaster', function () {
            return Http::baseUrl(config('services.ticketmaster.base_url'))->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.config('services.ticketmaster.key'),
            ]);
        });
    }
}
