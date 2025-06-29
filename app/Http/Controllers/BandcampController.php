<?php

namespace App\Http\Controllers;

use App\Actions\Bandcamp\SearchAlbumAction;
use Illuminate\Http\Request;

class BandcampController extends Controller
{
    public function searchAlbums(string $search, SearchAlbumAction $action): array
    {
        return $action->execute($search);
    }
}
