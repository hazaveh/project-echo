<?php

namespace App\Http\Controllers;

use App\Actions\Bandcamp\FetchAlbumDetailsAction;
use App\Actions\Bandcamp\FetchArtistAlbumsAction;
use App\Actions\Bandcamp\SearchAlbumAction;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;

class BandcampController extends Controller
{
    public function searchAlbums(string $search, SearchAlbumAction $action): array
    {
        return Cache::remember('bandcamp-albums-'.md5($search), 120, function () use ($search, $action) {
            return $action->execute($search);
        });
    }

    public function artistAlbums(string $username, FetchArtistAlbumsAction $action): array
    {
        return Cache::remember('bandcamp-artist-albums-'.md5($username), 120, function () use ($username, $action) {
            return $action->execute($username);
        });
    }

    public function albumDetails(string $username, string $albumSlug, FetchAlbumDetailsAction $action): array
    {
        $cacheKey = 'bandcamp-album-details-v2-'.md5($username.'-'.$albumSlug);

        return Cache::remember($cacheKey, 120, function () use ($username, $albumSlug, $action) {
            try {
                return $action->execute($username, $albumSlug);
            } catch (RuntimeException $exception) {
                report($exception);
                abort(404);
            }
        });
    }

    public function albumDetailsByType(string $username, string $releaseType, string $albumSlug, FetchAlbumDetailsAction $action): array
    {
        $cacheKey = 'bandcamp-album-details-v2-'.md5($username.'-'.$releaseType.'-'.$albumSlug);

        return Cache::remember($cacheKey, 120, function () use ($username, $releaseType, $albumSlug, $action) {
            try {
                return $action->executeByType($username, $releaseType, $albumSlug);
            } catch (InvalidArgumentException|RuntimeException $exception) {
                report($exception);
                abort(404);
            }
        });
    }
}
