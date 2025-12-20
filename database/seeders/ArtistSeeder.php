<?php

namespace Database\Seeders;

use App\Models\Artist;
use Illuminate\Database\Seeder;

class ArtistSeeder extends Seeder
{
    public function run(): void
    {
        Artist::upsert($this->data(), ['prn_artist_id'], ['spotify', 'name']);
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function data(): array
    {
        return [
            [
                'prn_artist_id' => 1,
                'spotify' => '079svMEXkbT5nGU2kfoqO2',
                'name' => 'God Is An Astronaut',
            ],
            [
                'prn_artist_id' => 2,
                'spotify' => '0d5ZwMtCer8dQdOPAgWhe7',
                'name' => 'Alcest',
            ],
            [
                'prn_artist_id' => 3,
                'spotify' => '53LVoipNTQ4lvUSJ61XKU3',
                'name' => 'MONO',
            ],
            [
                'prn_artist_id' => 4,
                'spotify' => '6YK58h9BCYpFNv10fsMwoS',
                'name' => 'pg.lost',
            ],
        ];
    }
}
