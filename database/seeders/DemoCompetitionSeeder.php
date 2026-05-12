<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Services\TournamentDrawService;

class DemoCompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $algorithm = RatingAlgorithm::active();
        $clubs = $this->seedClubs();
        $players = $this->seedPlayers($clubs);
        $tournaments = $this->seedTournaments($clubs);

        $demoUserIds = $players->pluck('id');

        MatchRecord::whereIn('submitted_by', $demoUserIds)->delete();
        RatingEvent::whereIn('user_id', $demoUserIds)->delete();

        PlayerProfile::whereIn('user_id', $demoUserIds)->update([
            'singles_rating' => $algorithm->settings['starting_rating'] ?? RatingAlgorithm::DEFAULT_SETTINGS['starting_rating'],
            'doubles_rating' => $algorithm->settings['starting_rating'] ?? RatingAlgorithm::DEFAULT_SETTINGS['starting_rating'],
            'singles_matches' => 0,
            'doubles_matches' => 0,
        ]);

        $this->seedMatches($players->values(), $clubs->values(), $tournaments->values());
        $this->seedTournamentOrganizerData($players->values(), $tournaments->values());
    }

    private function seedClubs()
    {
        $clubs = collect([
            ['name' => 'Smashr KL', 'slug' => 'smashr-kl', 'state' => 'Kuala Lumpur', 'city' => 'Kuala Lumpur'],
            ['name' => 'Petaling Jaya Racquets', 'slug' => 'petaling-jaya-racquets', 'state' => 'Selangor', 'city' => 'Petaling Jaya'],
            ['name' => 'Shah Alam Flight Club', 'slug' => 'shah-alam-flight-club', 'state' => 'Selangor', 'city' => 'Shah Alam'],
            ['name' => 'Penang Shuttle House', 'slug' => 'penang-shuttle-house', 'state' => 'Penang', 'city' => 'George Town'],
            ['name' => 'Johor Smash Lab', 'slug' => 'johor-smash-lab', 'state' => 'Johor', 'city' => 'Johor Bahru'],
            ['name' => 'Ipoh Net Masters', 'slug' => 'ipoh-net-masters', 'state' => 'Perak', 'city' => 'Ipoh'],
            ['name' => 'Melaka Rally Room', 'slug' => 'melaka-rally-room', 'state' => 'Melaka', 'city' => 'Melaka'],
            ['name' => 'Kota Kinabalu Court Co', 'slug' => 'kota-kinabalu-court-co', 'state' => 'Sabah', 'city' => 'Kota Kinabalu'],
            ['name' => 'Kuching Drop Shot', 'slug' => 'kuching-drop-shot', 'state' => 'Sarawak', 'city' => 'Kuching'],
            ['name' => 'Putrajaya Badminton Union', 'slug' => 'putrajaya-badminton-union', 'state' => 'Putrajaya', 'city' => 'Putrajaya'],
        ]);

        return $clubs->map(fn (array $club) => Club::updateOrCreate(
            ['slug' => $club['slug']],
            [
                'name' => $club['name'],
                'country' => 'Malaysia',
                'state' => $club['state'],
                'city' => $club['city'],
                'description' => 'A seeded Smashr badminton club for demo rankings, tournaments, and match context.',
            ],
        ));
    }

    private function seedPlayers($clubs)
    {
        $names = [
            'Aiman Hakimi', 'Siti Farah', 'Daniel Tan', 'Nur Izzati', 'Raj Kumar',
            'Mei Ling', 'Adam Zulkarnain', 'Priya Nair', 'Jason Wong', 'Alya Sofea',
            'Ben Lim', 'Nadia Rahman', 'Marcus Lee', 'Hana Ismail', 'Kevin Chua',
            'Sofia Chan', 'Amir Danish', 'Liyana Omar', 'Ethan Teo', 'Mira Yusof',
            'Irfan Syah', 'Qistina Azman', 'Farhan Rosli', 'Michelle Goh', 'Hakim Zain',
            'Nur Aisyah', 'Victor Chong', 'Anis Iman', 'Ryan Low', 'Sabrina Lim',
            'Faiz Ariff', 'Carmen Tan', 'Azri Hafiz', 'Daphne Wong', 'Rizal Hamid',
            'Elena Chew', 'Syafiq Rahim', 'Janice Foo', 'Harith Danish', 'Nora Lee',
            'Wei Han', 'Aqilah Zulkifli', 'Brandon Yap', 'Nurin Batrisya', 'Colin Teh',
            'Maisarah Yusri', 'Gavin Ong', 'Izz Amirul', 'Rachel Lau', 'Zara Imani',
        ];

        $demoPlayers = collect($names)->map(function (string $name, int $index) use ($clubs) {
            $email = 'demo.player'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'@smashr.test';
            $club = $clubs[$index % $clubs->count()];

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'suspended_at' => null,
                ],
            );

            PlayerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $name,
                    'slug' => 'demo-'.Str::slug($name),
                    'country' => 'Malaysia',
                    'state' => $club->state,
                    'city' => $club->city,
                    'preferred_hand' => $index % 5 === 0 ? 'left' : 'right',
                    'primary_format' => $index % 3 === 0 ? 'singles' : 'doubles',
                ],
            );

            $user->clubs()->syncWithoutDetaching([$club->id]);

            return $user->load('playerProfile', 'clubs');
        });

        $testUser = User::where('email', 'test@example.com')->first();

        if ($testUser) {
            PlayerProfile::updateOrCreate(
                ['user_id' => $testUser->id],
                [
                    'display_name' => 'Test User',
                    'slug' => 'test-user',
                    'country' => 'Malaysia',
                    'state' => 'Kuala Lumpur',
                    'city' => 'Kuala Lumpur',
                    'preferred_hand' => 'right',
                    'primary_format' => 'doubles',
                ],
            );
            $testUser->clubs()->syncWithoutDetaching([$clubs->first()->id]);

            return $demoPlayers->prepend($testUser->load('playerProfile', 'clubs'));
        }

        return $demoPlayers;
    }

    private function seedTournaments($clubs)
    {
        $tournaments = collect([
            ['name' => 'Smashr Malaysia Open', 'slug' => 'smashr-malaysia-open', 'club' => 0, 'city' => 'Kuala Lumpur', 'state' => 'Kuala Lumpur', 'days' => [-40, -38]],
            ['name' => 'Selangor Weekend Classic', 'slug' => 'selangor-weekend-classic', 'club' => 1, 'city' => 'Petaling Jaya', 'state' => 'Selangor', 'days' => [-30, -29]],
            ['name' => 'Northern Shuttle Cup', 'slug' => 'northern-shuttle-cup', 'club' => 3, 'city' => 'George Town', 'state' => 'Penang', 'days' => [-20, -18]],
            ['name' => 'Southern Smash Series', 'slug' => 'southern-smash-series', 'club' => 4, 'city' => 'Johor Bahru', 'state' => 'Johor', 'days' => [-12, -10]],
            ['name' => 'Borneo Flight Invitational', 'slug' => 'borneo-flight-invitational', 'club' => 7, 'city' => 'Kota Kinabalu', 'state' => 'Sabah', 'days' => [12, 14]],
        ]);

        return $tournaments->map(fn (array $tournament) => Tournament::updateOrCreate(
            ['slug' => $tournament['slug']],
            [
                'club_id' => $clubs[$tournament['club']]->id,
                'organizer_id' => User::where('email', 'admin@smashr.test')->value('id') ?? User::query()->value('id'),
                'name' => $tournament['name'],
                'country' => 'Malaysia',
                'state' => $tournament['state'],
                'city' => $tournament['city'],
                'venue' => $tournament['city'].' Badminton Arena',
                'starts_at' => now()->addDays($tournament['days'][0])->toDateString(),
                'ends_at' => now()->addDays($tournament['days'][1])->toDateString(),
                'status' => $tournament['days'][0] > 0 ? 'published' : 'archived',
                'registration_mode' => 'public',
                'registration_status' => $tournament['days'][0] > 0 ? 'open' : 'closed',
                'registration_deadline' => now()->addDays($tournament['days'][0] - 2)->toDateString(),
            ],
        ));
    }

    private function seedTournamentOrganizerData($players, $tournaments): void
    {
        foreach ($tournaments as $tournamentIndex => $tournament) {
            $categories = collect([
                ['name' => 'Open Singles', 'format' => 'singles', 'draw_mode' => 'single_elimination'],
                ['name' => 'Amateur Doubles', 'format' => 'doubles', 'draw_mode' => 'round_robin'],
                ['name' => 'New Talent Mixed', 'format' => 'mixed', 'draw_mode' => 'single_elimination'],
            ])->map(fn (array $category) => $tournament->categories()->updateOrCreate(
                ['slug' => str($category['name'])->slug()],
                [
                    'name' => $category['name'],
                    'format' => $category['format'],
                    'level_label' => str($category['name'])->beforeLast(' ')->toString(),
                    'draw_mode' => $category['draw_mode'],
                    'max_entrants' => $tournament->slug === 'borneo-flight-invitational' ? 64 : 16,
                    'status' => 'published',
                ],
            ));

            foreach ($categories as $categoryIndex => $category) {
                $category->entrants()->delete();

                $entrantCount = $this->tournamentEntrantCount($tournament->slug, $category->format);

                for ($entry = 0; $entry < $entrantCount; $entry++) {
                    $entrant = $tournament->entrants()->create([
                        'tournament_category_id' => $category->id,
                        'created_by' => $players[($tournamentIndex + $entry) % $players->count()]->id,
                        'status' => 'approved',
                        'seed' => $entry + 1,
                    ]);
                    $entrant->players()->create([
                        'user_id' => $players[($tournamentIndex + $categoryIndex + $entry) % $players->count()]->id,
                        'position' => 1,
                    ]);

                    if ($category->format !== 'singles') {
                        $entrant->players()->create([
                            'user_id' => $players[($tournamentIndex + $categoryIndex + $entry + 7) % $players->count()]->id,
                            'position' => 2,
                        ]);
                    }
                }

                app(TournamentDrawService::class)->generate($category->fresh());
            }
        }
    }

    private function tournamentEntrantCount(string $tournamentSlug, string $format): int
    {
        if ($tournamentSlug !== 'borneo-flight-invitational') {
            return 4;
        }

        return $format === 'singles' ? 50 : 25;
    }

    private function seedMatches($players, $clubs, $tournaments): void
    {
        for ($index = 0; $index < 50; $index++) {
            $format = $index % 3 === 0 ? 'singles' : 'doubles';
            $club = $clubs[$index % $clubs->count()];
            $tournament = $index % 2 === 0 ? $tournaments[$index % $tournaments->count()] : null;
            $playedAt = $tournament?->slug === 'borneo-flight-invitational'
                ? $tournament->starts_at->toDateString()
                : now()->subDays(65 - $index)->toDateString();
            $winnerSide = $index % 4 === 0 ? 'B' : 'A';

            $match = MatchRecord::create([
                'format' => $format,
                'submitted_by' => $players[$index % $players->count()]->id,
                'club_id' => $club->id,
                'tournament_id' => $tournament?->id,
                'status' => 'pending_confirmation',
                'played_at' => $playedAt,
                'score' => $this->score($winnerSide, $index),
                'winner_side' => $winnerSide,
            ]);

            $lineup = $this->lineup($players, $index, $format);

            foreach ($lineup as [$user, $side, $position]) {
                $match->players()->create([
                    'user_id' => $user->id,
                    'side' => $side,
                    'position' => $position,
                    'confirmed_at' => now(),
                ]);
            }

            app(RatingService::class)->confirmAsAdmin($match);
        }
    }

    private function lineup($players, int $index, string $format): array
    {
        $count = $players->count();

        if ($format === 'singles') {
            return [
                [$players[$index % $count], 'A', 1],
                [$players[($index + 7) % $count], 'B', 1],
            ];
        }

        return [
            [$players[$index % $count], 'A', 1],
            [$players[($index + 3) % $count], 'A', 2],
            [$players[($index + 8) % $count], 'B', 1],
            [$players[($index + 13) % $count], 'B', 2],
        ];
    }

    private function score(string $winnerSide, int $index): array
    {
        $close = $index % 5;
        $loserPoints = [18, 16, 14, 19, 17][$close];

        if ($winnerSide === 'A') {
            return [
                ['a' => 21, 'b' => $loserPoints],
                ['a' => $index % 7 === 0 ? 19 : 21, 'b' => $index % 7 === 0 ? 21 : max(11, $loserPoints - 2)],
                ['a' => 21, 'b' => max(10, $loserPoints - 4)],
            ];
        }

        return [
            ['a' => $loserPoints, 'b' => 21],
            ['a' => max(12, $loserPoints - 1), 'b' => 21],
        ];
    }
}
