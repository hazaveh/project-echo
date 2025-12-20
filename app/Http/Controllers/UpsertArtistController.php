<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertArtistRequest;
use App\Models\Artist;
use Illuminate\Http\JsonResponse;

class UpsertArtistController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpsertArtistRequest $request): JsonResponse
    {
        $data = $request->validated();

        $attributes = [
            'name' => $data['name'],
        ];

        $fieldMap = [
            'spotify' => 'spotify',
            'instagram' => 'instagram',
            'twitter' => 'twitter',
            'facebook' => 'facebook',
            'youtube' => 'youtube',
            'homepage' => 'homepage',
            'apple' => 'apple_music',
        ];

        foreach ($fieldMap as $inputKey => $column) {
            if (array_key_exists($inputKey, $data)) {
                $attributes[$column] = $data[$inputKey];
            }
        }

        $artist = Artist::updateOrCreate(
            ['prn_artist_id' => $data['prn_artist_id']],
            $attributes
        );

        return response()->json([
            'status' => 'ok',
            'artist' => $artist,
        ]);
    }
}
