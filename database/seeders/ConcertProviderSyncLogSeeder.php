<?php

namespace Database\Seeders;

use App\Models\ConcertProviderSyncLog;
use Illuminate\Database\Seeder;

class ConcertProviderSyncLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConcertProviderSyncLog::factory()->count(5)->create();
    }
}
