<?php

namespace App\Http\Controllers;

use App\Actions\Bandcamp\SearchAlbumAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BandcampController extends Controller
{
    public function searchAlbums(string $search, SearchAlbumAction $action): array
    {
        return Cache::remember("bandcamp-albums-" . md5($search), 60, function () use ($search, $action) {
            return $action->execute($search);
        });
    }
}
