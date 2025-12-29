<?php

namespace App\Actions\Affiliates;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GenerateAwinLinkAction
{
    private const BASE_URL = 'https://api.awin.com';

    private const ENDPOINT = '/publishers/%s/linkbuilder/generate';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 5;

    public function execute(string $originalUrl, string $advertiserId): string
    {
        if ($originalUrl === '') {
            throw new InvalidArgumentException('Awin destination URL is required.');
        }

        $publisherId = config('services.awin.publisher_id');
        $accessToken = config('services.awin.api_key');

        if (is_int($publisherId)) {
            $publisherId = (string) $publisherId;
        }

        if (! is_string($publisherId) || $publisherId === '' || ! ctype_digit($publisherId)) {
            throw new RuntimeException('Awin publisher ID is missing or invalid.');
        }

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Awin access token is missing.');
        }

        if (! ctype_digit($advertiserId)) {
            throw new InvalidArgumentException('Awin advertiser ID must be numeric.');
        }

        $payload = [
            'advertiserId' => (int) $advertiserId,
            'destinationUrl' => $originalUrl,
        ];

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Authorization' => $accessToken,
                ])
                ->withQueryParameters([
                    'accessToken' => $accessToken,
                ])
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->post(sprintf(self::ENDPOINT, $publisherId), $payload);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Awin link builder request threw an exception.', [
                'publisher_id' => $publisherId,
                'advertiser_id' => $advertiserId,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Awin link builder request failed.', previous: $exception);
        }

        if ($response->failed()) {
            Log::warning('Awin link builder request failed.', [
                'publisher_id' => $publisherId,
                'advertiser_id' => $advertiserId,
                'status' => $response->status(),
                'payload' => $response->json(),
            ]);

            throw new RuntimeException('Awin link builder request failed with status '.$response->status().'.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Awin link builder response was invalid.');
        }

        $shortUrl = data_get($data, 'shortUrl');
        $url = is_string($shortUrl) && $shortUrl !== ''
            ? $shortUrl
            : data_get($data, 'url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Awin link builder response did not include a URL.');
        }

        return $url;
    }
}
