<?php

namespace App\Http\Controllers\Jambase;

use App\Actions\Sources\Jambase\FetchArtistAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetArtistController extends Controller
{
    public function __invoke(FetchArtistAction $action, Request $request, string $spotifyId)
    {
        return $action->execute($spotifyId, 'spotify');
    }
}
