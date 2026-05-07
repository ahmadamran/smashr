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
        ];

        return collect($names)->map(function (string $name, int $index) use ($clubs) {
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

            $user->clubs()->sync([$club->id]);

            return $user->load('playerProfile', 'clubs');
        });
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
                'name' => $tournament['name'],
                'country' => 'Malaysia',
                'state' => $tournament['state'],
                'city' => $tournament['city'],
                'starts_at' => now()->addDays($tournament['days'][0])->toDateString(),
                'ends_at' => now()->addDays($tournament['days'][1])->toDateString(),
                'status' => $tournament['days'][0] > 0 ? 'published' : 'archived',
            ],
        ));
    }

    private function seedMatches($players, $clubs, $tournaments): void
    {
        for ($index = 0; $index < 50; $index++) {
            $format = $index % 3 === 0 ? 'singles' : 'doubles';
            $club = $clubs[$index % $clubs->count()];
            $tournament = $index % 2 === 0 ? $tournaments[$index % $tournaments->count()] : null;
            $playedAt = now()->subDays(65 - $index)->toDateString();
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
