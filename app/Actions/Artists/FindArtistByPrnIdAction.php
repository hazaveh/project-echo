<?php

namespace App\Actions\Artists;

use App\Models\Artist;

class FindArtistByPrnIdAction
{
    public function execute(int $prnArtistId): ?Artist
    {
        return Artist::findByPrnArtistId($prnArtistId);
    }
}
