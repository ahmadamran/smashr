<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tournaments\Services\TournamentSoftwareImportService;

class MssjJohorTournamentSoftwareSeeder extends Seeder
{
    public function run(TournamentSoftwareImportService $importer): void
    {
        $importer->import(config: [
            'snapshot_path' => 'seeders/data/mssj_johor_2026_entries.json',
            'players_url' => 'https://www.tournamentsoftware.com/tournament/4C353A6C-8C5F-4BA6-8992-6D2BE4721533/players',
            'slug' => 'kejohanan-badminton-mssj-2026',
            'name' => 'KEJOHANAN BADMINTON MAJLIS SUKAN SEKOLAH JOHOR (MSSJ) 2026',
            'country' => 'Malaysia',
            'state' => 'Johor',
            'city' => 'Kota Tinggi',
            'venue' => 'Kota Tinggi',
            'starts_at' => '2026-05-05',
            'ends_at' => '2026-05-07',
            'registration_deadline' => '2026-05-04',
            'email_prefix' => 'ts-4c353-',
            'profile_slug_prefix' => 'mssj-johor-2026-',
            'events' => [
                'PL12' => ['name' => 'Boys Under 12', 'format' => 'singles', 'level_label' => 'Under 12 Boys'],
                'PL15' => ['name' => 'Boys Under 15', 'format' => 'singles', 'level_label' => 'Under 15 Boys'],
                'PL18' => ['name' => 'Boys Under 18', 'format' => 'singles', 'level_label' => 'Under 18 Boys'],
                'PP12' => ['name' => 'Girls Under 12', 'format' => 'singles', 'level_label' => 'Under 12 Girls'],
                'PP15' => ['name' => 'Girls Under 15', 'format' => 'singles', 'level_label' => 'Under 15 Girls'],
                'PP18' => ['name' => 'Girls Under 18', 'format' => 'singles', 'level_label' => 'Under 18 Girls'],
            ],
        ]);
    }
}
