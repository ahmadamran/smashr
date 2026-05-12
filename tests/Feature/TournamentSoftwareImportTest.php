<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clubs\Models\Club;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Services\TournamentSoftwareImportService;
use RuntimeException;
use Tests\TestCase;

class TournamentSoftwareImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_maps_player_rows_to_event_codes(): void
    {
        $players = app(TournamentSoftwareImportService::class)->parsePlayers($this->playersHtml());

        $this->assertSame([
            ['name' => 'Ali Bin Ahmad', 'events' => ['L12']],
            ['name' => 'Nur Aisyah Binti Musa', 'events' => ['P15']],
            ['name' => 'Ali Bin Ahmad', 'events' => ['L15']],
            ['name' => 'Siti Aminah', 'events' => ['P12', 'P15']],
            ['name' => 'Chong Wei Kit', 'events' => ['L12']],
            ['name' => 'Tan Jun Hao', 'events' => ['L15']],
        ], $players->all());
    }

    public function test_import_creates_tournament_categories_entrants_and_draws(): void
    {
        User::factory()->create(['email' => 'admin@smashr.test', 'name' => 'Admin']);

        $tournament = app(TournamentSoftwareImportService::class)->import($this->playersHtml());

        $this->assertSame('MSS MELAKA BADMINTON 2026', $tournament->name);
        $this->assertSame('2026-05-12', $tournament->starts_at->toDateString());
        $this->assertSame('Melaka', $tournament->state);
        $this->assertSame([
            'l12' => 'Boys Under 12',
            'l15' => 'Boys Under 15',
            'l18' => 'Boys Under 18',
            'p12' => 'Girls Under 12',
            'p15' => 'Girls Under 15',
            'p18' => 'Girls Under 18',
        ], $tournament->categories()->orderBy('slug')->pluck('name', 'slug')->all());

        $this->assertSame(2, $tournament->categories()->where('slug', 'l12')->firstOrFail()->entrants()->count());
        $this->assertSame(1, $tournament->categories()->where('slug', 'l12')->firstOrFail()->matches()->count());
        $this->assertSame(1, User::where('email', 'like', '%@import.smashr.test')->where('name', 'Ali Bin Ahmad')->count());
        $this->assertSame(5, User::where('email', 'like', '%@import.smashr.test')->count());
        $this->assertSame('male', User::where('name', 'Ali Bin Ahmad')->firstOrFail()->playerProfile->gender);
        $this->assertSame('female', User::where('name', 'Nur Aisyah Binti Musa')->firstOrFail()->playerProfile->gender);

        app(TournamentSoftwareImportService::class)->import($this->playersHtml());

        $this->assertSame(1, Tournament::where('slug', 'mss-melaka-badminton-2026')->count());
        $this->assertSame(5, User::where('email', 'like', '%@import.smashr.test')->count());
    }

    public function test_snapshot_import_creates_school_clubs_without_replacing_existing_memberships(): void
    {
        app(TournamentSoftwareImportService::class)->import();

        $schoolClub = Club::where('name', 'SJKC CHUNG HWA')->firstOrFail();
        $user = User::where('email', 'ts-e4153-406@import.smashr.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@smashr.test',
        ]);
        $this->assertTrue($user->clubs()->whereKey($schoolClub->id)->exists());
        $this->assertSame('Malaysia', $schoolClub->country);
        $this->assertSame('Melaka', $schoolClub->state);
        $this->assertSame('Melaka', $schoolClub->city);
        $this->assertDatabaseHas('tournament_entrant_players', ['user_id' => $user->id, 'school_name' => 'SJKC CHUNG HWA']);

        $extraClub = Club::create([
            'name' => 'Existing Training Club',
            'slug' => 'existing-training-club',
            'country' => 'Malaysia',
            'state' => 'Selangor',
            'city' => 'Shah Alam',
            'description' => 'Existing membership that import must preserve.',
        ]);

        $user->clubs()->syncWithoutDetaching([$extraClub->id]);

        app(TournamentSoftwareImportService::class)->import();

        $user->refresh();

        $this->assertSame(1, Club::where('name', 'SJKC CHUNG HWA')->count());
        $this->assertTrue($user->clubs()->whereKey($schoolClub->id)->exists());
        $this->assertTrue($user->clubs()->whereKey($extraClub->id)->exists());
    }

    public function test_import_fails_when_players_are_not_exposed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('did not expose player rows');

        app(TournamentSoftwareImportService::class)->import('<html><body><h1>MSS MELAKA BADMINTON 2026 Players</h1></body></html>');
    }

    private function playersHtml(): string
    {
        return <<<'HTML'
            <table>
                <tr><th>No</th><th>Player</th><th>Events</th></tr>
                <tr><td>1</td><td>Ali Bin Ahmad</td><td>L12</td></tr>
                <tr><td>2</td><td>Nur Aisyah Binti Musa</td><td>P15</td></tr>
                <tr><td>3</td><td>Ali Bin Ahmad</td><td>L15</td></tr>
                <tr><td>4</td><td>Siti Aminah</td><td>P12 P15</td></tr>
                <tr><td>5</td><td>Chong Wei Kit</td><td>L12</td></tr>
                <tr><td>6</td><td>Tan Jun Hao</td><td>L15</td></tr>
            </table>
        HTML;
    }
}
