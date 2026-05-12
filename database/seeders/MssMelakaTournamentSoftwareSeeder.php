<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tournaments\Services\TournamentSoftwareImportService;

class MssMelakaTournamentSoftwareSeeder extends Seeder
{
    public function run(TournamentSoftwareImportService $importer): void
    {
        $importer->import();
    }
}
