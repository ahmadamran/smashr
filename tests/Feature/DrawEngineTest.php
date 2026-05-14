<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Players\Models\PlayerProfile;
use Modules\Tournaments\DrawEngine\Services\DrawGeneratorService;
use Modules\Tournaments\DrawEngine\Services\ScheduleGeneratorService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Tests\TestCase;

class DrawEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_draw_engine_url_redirects_to_merged_draws_page_for_organizer(): void
    {
        $organizer = $this->player('Engine Owner');
        $tournament = $this->tournament($organizer);
        $this->category($tournament, 'Open Singles', 'singles');

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.draw-engine', $tournament))
            ->assertRedirect(route('organizer.tournaments.draws', $tournament));

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.draws', $tournament))
            ->assertOk()
            ->assertSee('Draws')
            ->assertSee('Manage draw')
            ->assertSee('Open Singles')
            ->assertDontSee('>Draw Engine</a>', false);

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.draws', ['tournament' => $tournament, 'category' => $tournament->categories->first()->slug]))
            ->assertRedirect(route('organizer.tournaments.draws.manage', [$tournament, $tournament->categories->first()]));

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.draws.manage', [$tournament, $tournament->categories->first()]))
            ->assertOk()
            ->assertSee('Generate draw and schedule')
            ->assertSee('Open Singles has 0 approved entrants and 0 generated matches.');
    }

    public function test_generators_support_all_draw_types(): void
    {
        $organizer = $this->player('Generator Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Generator Singles', 'singles');

        foreach (range(1, 8) as $seed) {
            $this->entrant($tournament, $category, [$this->player('Generator Player '.$seed)], 'approved', $seed);
        }

        $draws = app(DrawGeneratorService::class);

        $single = $draws->preview($category, 'single_elimination');
        $this->assertSame('single_elimination', $single['draw_type']);
        $this->assertCount(4, $single['matches']);

        $double = $draws->preview($category, 'double_elimination');
        $this->assertSame('double_elimination', $double['draw_type']);
        $this->assertNotEmpty(collect($double['matches'])->where('stage', 'losers')->first()['feed_rule']);

        $roundRobin = $draws->preview($category, 'round_robin', ['group_size' => 4]);
        $this->assertSame('round_robin', $roundRobin['draw_type']);
        $this->assertCount(12, $roundRobin['matches']);

        $poolKnockout = $draws->preview($category, 'pool_to_knockout', ['group_size' => 4, 'qualifiers_per_pool' => 2]);
        $this->assertSame('pool_to_knockout', $poolKnockout['draw_type']);
        $this->assertNotEmpty(collect($poolKnockout['matches'])->where('stage', 'knockout')->first()['feed_rule']);
    }

    public function test_scheduler_respects_court_window_rest_and_player_limits(): void
    {
        $organizer = $this->player('Schedule Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Schedule Pool', 'singles', 'round_robin');

        foreach (range(1, 3) as $seed) {
            $this->entrant($tournament, $category, [$this->player('Schedule Player '.$seed)], 'approved', $seed);
        }

        $draw = app(DrawGeneratorService::class)->preview($category, 'round_robin', ['group_size' => 3]);
        $scheduled = app(ScheduleGeneratorService::class)->schedule($tournament, $draw, [
            'match_duration_minutes' => 30,
            'rest_minutes' => 30,
            'max_matches_per_player_per_day' => 1,
            'days' => [[
                'date' => $tournament->starts_at->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'courts_count' => 1,
                'allowed_stages' => ['pool'],
            ]],
        ]);

        $this->assertSame(1, collect($scheduled['matches'])->whereNotNull('scheduled_at')->count());
        $this->assertNotEmpty($scheduled['warnings']);
    }

    public function test_generate_persists_matches_and_requires_overwrite_confirmation(): void
    {
        $organizer = $this->player('Persist Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Persist Singles', 'singles');

        foreach (range(1, 4) as $seed) {
            $this->entrant($tournament, $category, [$this->player('Persist Player '.$seed)], 'approved', $seed);
        }

        $payload = [
            'event_id' => $category->id,
            'draw_type' => 'single_elimination',
            'courts_count' => 2,
            'schedule_start_time' => '09:00',
            'schedule_end_time' => '18:00',
            'match_duration_minutes' => 30,
            'rest_minutes' => 10,
        ];

        $firstResponse = $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate-engine', [$tournament, $category]), $payload);

        $firstResponse->assertSessionHasNoErrors();
        $firstResponse->assertRedirect();

        $this->assertSame(2, $category->matches()->count());

        $response = $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate-engine', [$tournament, $category]), $payload);

        $response->assertRedirect();
        $errors = $response->baseResponse->getSession()->get('errors');
        $this->assertTrue(
            is_array($errors)
                ? str_contains(json_encode($errors), 'confirm_overwrite')
                : $errors->has('confirm_overwrite')
        );

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate-engine', [$tournament, $category]), [...$payload, 'confirm_overwrite' => 1])
            ->assertRedirect();

        $this->assertSame(2, $category->matches()->count());
    }

    private function tournament(User $organizer): Tournament
    {
        return Tournament::create([
            'organizer_id' => $organizer->id,
            'name' => 'Draw Engine Tournament '.$organizer->id,
            'slug' => 'draw-engine-tournament-'.$organizer->id,
            'country' => 'Malaysia',
            'state' => 'Kuala Lumpur',
            'city' => 'Kuala Lumpur',
            'venue' => 'Arena One',
            'starts_at' => now()->addDays(7)->toDateString(),
            'ends_at' => now()->addDays(8)->toDateString(),
            'status' => 'published',
            'registration_mode' => 'public',
            'registration_status' => 'open',
        ]);
    }

    private function category(Tournament $tournament, string $name, string $format, string $drawMode = 'single_elimination'): TournamentCategory
    {
        return $tournament->categories()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(100, 999),
            'format' => $format,
            'draw_mode' => $drawMode,
            'group_size' => 4,
            'status' => 'published',
        ]);
    }

    private function entrant(Tournament $tournament, TournamentCategory $category, array $players, string $status, ?int $seed = null): TournamentEntrant
    {
        $entrant = $tournament->entrants()->create([
            'tournament_category_id' => $category->id,
            'created_by' => $players[0]->id,
            'status' => $status,
            'seed' => $seed,
        ]);

        foreach ($players as $index => $player) {
            $entrant->players()->create(['user_id' => $player->id, 'position' => $index + 1]);
        }

        return $entrant->load('players.user.playerProfile');
    }

    private function player(string $name): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => str($name)->slug().'-'.fake()->unique()->numberBetween(100, 999).'@example.com',
        ]);

        PlayerProfile::create([
            'user_id' => $user->id,
            'display_name' => $name,
            'slug' => str($name)->slug().'-'.$user->id,
            'country' => 'Malaysia',
            'state' => 'Kuala Lumpur',
            'city' => 'Kuala Lumpur',
            'preferred_hand' => 'right',
            'primary_format' => 'doubles',
        ]);

        return $user->load('playerProfile');
    }
}
