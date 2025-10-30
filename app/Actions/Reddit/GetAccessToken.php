<?php

namespace App\Actions\Reddit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GetAccessToken
{
    public function execute(): string
    {
        $credentials = config('services.reddit');

        return Cache::remember('reddit_access_token', 60, function () use ($credentials): string {
            $response = Http::withBasicAuth($credentials['client_id'], $credentials['client_secret'])
                ->asForm()
                ->post('https://www.reddit.com/api/v1/access_token', [
                    'grant_type' => 'password',
                    'username' => $credentials['username'],
                    'password' => $credentials['password'],
                ]);

            return $response->json('access_token');
        });
    }
}
