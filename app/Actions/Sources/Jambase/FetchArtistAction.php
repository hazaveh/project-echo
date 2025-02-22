<?php

namespace App\Actions\Sources\Jambase;

use App\DTO\Response\Jambase\SearchArtistDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class FetchArtistAction
{
    const ENDPOINT = 'artists/id/%s:%s';

    public function execute(string $artistId, string $source)
    {
        $payload = [
            'apikey' => config('services.jambase.key'),
            'expandExternalIdentifiers' => 'true',
            'expandUpcomingEvents' => 'true',
        ];

        $response = Http::jambase()->get(sprintf(self::ENDPOINT, $source, $artistId), $payload);

        if ($response->failed()) {
            return response($response->body(), $response->status());
        }

        return SearchArtistDTO::fromResponse($this->validate($response->json()));
    }

    private function validate(array $response)
    {
        $validated = Validator::make($response, [
            'artist' => 'required|array',
            'artist.name' => 'required|string',
            'artist.identifier' => 'required|string',
            'artist.sameAs' => 'required|array',
            'artist.events' => 'required|array',
        ]);

        if ($validated->fails()) {
            throw new \Exception($validated->errors()->first());
        }

        return $response['artist'];
    }
}
