<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        Artist::upsert($this->data(), ['spotify_id']);
    }

    private function data()
    {
        return [
            ['name' => 'God Is An Astronaut', 'spotify_id' => '079svMEXkbT5nGU2kfoqO2'],
            ['name' => 'Alcest', 'spotify_id' => '0d5ZwMtCer8dQdOPAgWhe7'],
            ['name' => 'MONO', 'spotify_id' => '53LVoipNTQ4lvUSJ61XKU3'],
            ['name' => 'pg.lost', 'spotify_id' => '6YK58h9BCYpFNv10fsMwoS'],
        ];
    }
}
