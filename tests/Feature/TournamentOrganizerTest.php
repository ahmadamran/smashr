<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingEvent;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Services\TournamentDrawService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TournamentOrganizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_create_tournament_and_user_can(): void
    {
        $this->get(route('organizer.tournaments.create'))->assertRedirect('/login');

        $organizer = $this->player('Organizer One');

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.store'), [
                'name' => 'Community Open',
                'country' => 'Malaysia',
                'state' => 'Selangor',
                'city' => 'Petaling Jaya',
                'venue' => 'Court Hall A',
                'starts_at' => now()->addDays(10)->toDateString(),
                'ends_at' => now()->addDays(11)->toDateString(),
                'status' => 'published',
                'registration_mode' => 'public',
                'registration_status' => 'open',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tournaments', [
            'name' => 'Community Open',
            'organizer_id' => $organizer->id,
            'registration_mode' => 'public',
        ]);
    }

    public function test_non_owner_cannot_manage_but_superadmin_can(): void
    {
        $owner = $this->player('Owner User');
        $other = $this->player('Other User');
        $admin = $this->player('Admin User');
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $tournament = $this->tournament($owner);

        $this->actingAs($other)->get(route('organizer.tournaments.edit', $tournament))->assertForbidden();
        $this->actingAs($admin)->get(route('organizer.tournaments.edit', $tournament))->assertOk();
    }

    public function test_public_registration_creates_pending_entrant_and_private_blocks(): void
    {
        $organizer = $this->player('Registration Owner');
        $player = $this->player('Registration Player');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Open Singles', 'singles');
        Storage::fake('local');

        $this->actingAs($player)
            ->post(route('tournaments.register', $tournament), [
                'tournament_category_id' => $category->id,
                'contact_name' => 'Registration Player',
                'contact_phone' => '+60123456789',
                'identity_type' => 'ic',
                'identity_number' => '900101-10-1234',
                'identity_document' => UploadedFile::fake()->image('ic.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_entrants', [
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'contact_phone' => '+60123456789',
            'identity_type' => 'ic',
            'status' => 'pending',
            'kyc_status' => 'pending',
        ]);
        $this->assertNotNull(TournamentEntrant::where('tournament_id', $tournament->id)->value('identity_document_path'));

        $tournament->forceFill(['registration_mode' => 'private'])->save();

        $this->actingAs($player)
            ->post(route('tournaments.register', $tournament), [
                'tournament_category_id' => $category->id,
                'contact_name' => 'Registration Player',
                'contact_phone' => '+60123456789',
                'identity_type' => 'ic',
                'identity_number' => '900101-10-1234',
                'identity_document' => UploadedFile::fake()->image('ic.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_organizer_can_approve_and_add_doubles_individual_entrant(): void
    {
        $organizer = $this->player('Doubles Owner');
        $player = $this->player('Doubles Player');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Amateur Doubles', 'doubles');
        $entrant = $this->entrant($tournament, $category, [$player], 'pending');

        $this->actingAs($organizer)
            ->patch(route('organizer.tournaments.entrants.update', [$tournament, $entrant]), [
                'status' => 'approved',
                'seed' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('approved', $entrant->fresh()->status);
        $this->assertSame(1, $entrant->fresh()->players()->count());
    }

    public function test_public_players_tabs_show_seeded_and_confirmed_winners(): void
    {
        $organizer = $this->player('Tabs Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Tabbed Singles', 'singles');
        $seeded = $this->entrant($tournament, $category, [$this->player('Seeded Entrant')], 'approved', 1, ['Seeded Club']);
        $winner = $this->entrant($tournament, $category, [$this->player('Winning Entrant')], 'approved', 2, ['Winner Club']);
        $this->entrant($tournament, $category, [$this->player('Regular Entrant')], 'approved');
        $this->entrant($tournament, $category, [$this->player('Fourth Entrant')], 'approved');

        $final = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $organizer->id,
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [
                ['a' => 18, 'b' => 21],
                ['a' => 19, 'b' => 21],
            ],
            'winner_side' => 'B',
            'draw_round' => 2,
            'draw_position' => 1,
        ]);
        $final->players()->create(['user_id' => $seeded->players->first()->user_id, 'side' => 'A', 'position' => 1]);
        $final->players()->create(['user_id' => $winner->players->first()->user_id, 'side' => 'B', 'position' => 1]);

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.registrations', $tournament))
            ->assertOk()
            ->assertSee('Entrants')
            ->assertDontSee('Champion');

        $this->get(route('tournaments.players', ['tournament' => $tournament, 'tab' => 'seeded']))
            ->assertOk()
            ->assertSee('Tournament seeds')
            ->assertSee('Seeded Entrant')
            ->assertSee('Winning Entrant')
            ->assertDontSee('Regular Entrant');

        $this->get(route('tournaments.winners', $tournament))
            ->assertOk()
            ->assertSee('Tournament winners')
            ->assertSee('Top 4 finishers')
            ->assertSee('Tabbed Singles')
            ->assertSee('Winning Entrant')
            ->assertSee('Winner Club')
            ->assertSee('Seeded Entrant')
            ->assertDontSee('18-21, 19-21');
    }

    public function test_public_winners_use_highest_confirmed_draw_round_as_final(): void
    {
        $organizer = $this->player('Imported Winner Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Imported Girls Under 18', 'singles');
        $semifinalLoser = $this->entrant($tournament, $category, [$this->player('Imported Semifinal Loser')], 'approved', 1);
        $runnerUp = $this->entrant($tournament, $category, [$this->player('Imported Runner Up')], 'approved', 2);
        $champion = $this->entrant($tournament, $category, [$this->player('Imported Champion')], 'approved', 3);

        $semifinal = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $organizer->id,
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [
                ['a' => 21, 'b' => 10],
                ['a' => 21, 'b' => 19],
            ],
            'winner_side' => 'A',
            'draw_round' => 6,
            'draw_position' => 1,
        ]);
        $semifinal->players()->create(['user_id' => $runnerUp->players->first()->user_id, 'side' => 'A', 'position' => 1]);
        $semifinal->players()->create(['user_id' => $semifinalLoser->players->first()->user_id, 'side' => 'B', 'position' => 1]);

        $final = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $organizer->id,
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [
                ['a' => 21, 'b' => 15],
                ['a' => 9, 'b' => 21],
                ['a' => 13, 'b' => 21],
            ],
            'winner_side' => 'B',
            'draw_round' => 7,
            'draw_position' => 1,
        ]);
        $final->players()->create(['user_id' => $runnerUp->players->first()->user_id, 'side' => 'A', 'position' => 1]);
        $final->players()->create(['user_id' => $champion->players->first()->user_id, 'side' => 'B', 'position' => 1]);

        $this->get(route('tournaments.winners', $tournament))
            ->assertOk()
            ->assertSeeInOrder([
                'Imported Girls Under 18',
                '1',
                'Imported Champion',
                '2',
                'Imported Runner Up',
            ]);
    }

    public function test_registration_tabs_hide_seeded_and_winners_when_empty_and_user_search_filters_players(): void
    {
        $organizer = $this->player('Search Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Search Singles', 'singles');
        $this->entrant($tournament, $category, [$this->player('Unseeded Entrant')], 'approved');
        $target = $this->player('Ajax Search Player');
        $target->playerProfile->forceFill([
            'singles_rating' => 3.765,
            'doubles_rating' => 3.456,
        ])->save();
        $this->player('Other Search Player');

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.registrations', $tournament))
            ->assertOk()
            ->assertDontSee('Tournament seeds')
            ->assertDontSee('Tournament winners');

        $this->actingAs($organizer)
            ->getJson(route('organizer.tournaments.entrants.user-search', ['tournament' => $tournament, 'q' => 'ajax']))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $target->id,
                'name' => 'Ajax Search Player',
                'singles' => '3.765',
                'doubles' => '3.456',
            ])
            ->assertJsonMissing([
                'name' => 'Other Search Player',
            ]);
    }

    public function test_single_elimination_and_round_robin_draw_generation(): void
    {
        $organizer = $this->player('Draw Owner');
        $tournament = $this->tournament($organizer);
        $knockout = $this->category($tournament, 'Open Singles', 'singles', 'single_elimination');
        $roundRobin = $this->category($tournament, 'New Talent Doubles', 'doubles', 'round_robin');

        $players = collect(range(1, 8))->map(fn ($i) => $this->player('Draw Player '.$i));
        foreach ($players->take(4)->values() as $index => $player) {
            $this->entrant($tournament, $knockout, [$player], 'approved', $index + 1);
        }
        foreach ($players->chunk(2)->values() as $index => $pair) {
            $this->entrant($tournament, $roundRobin, $pair->values()->all(), 'approved', $index + 1);
        }

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate.tournament', $tournament), [
                'category_ids' => [$knockout->id, $roundRobin->id],
                'courts_count' => 2,
                'court_label_prefix' => 'Arena',
                'first_court_number' => 3,
                'schedule_start_time' => '10:30',
                'match_duration_minutes' => 25,
            ])
            ->assertRedirect();
        $this->assertSame(2, $knockout->matches()->count());
        $this->assertSame(['Arena 3', 'Arena 4'], $knockout->matches()->orderBy('draw_position')->pluck('court_label')->all());
        $this->assertSame(['10:30', '10:30'], $knockout->matches()->orderBy('draw_position')->get()->map(fn ($match) => $match->scheduled_at->format('H:i'))->all());
        $this->assertSame([25, 25], $knockout->matches()->orderBy('draw_position')->pluck('estimated_duration_minutes')->all());
        $this->assertSame(6, $roundRobin->matches()->count());
        $this->assertSame(['10:55', '10:55'], $roundRobin->matches()->orderBy('draw_position')->limit(2)->get()->map(fn ($match) => $match->scheduled_at->format('H:i'))->all());
        $this->assertNotNull($roundRobin->matches()->first()?->score_sheet_token);
    }

    public function test_single_elimination_draw_uses_standard_bye_formula(): void
    {
        $organizer = $this->player('Bye Formula Owner');
        $tournament = $this->tournament($organizer);
        $cases = [
            4 => 2,
            5 => 1,
            25 => 9,
            50 => 18,
        ];

        foreach ($cases as $entrantCount => $expectedMatches) {
            $category = $this->category($tournament, 'Bye Formula '.$entrantCount, 'singles');

            foreach (range(1, $entrantCount) as $seed) {
                $this->entrant($tournament, $category, [$this->player('Bye Formula '.$entrantCount.' Player '.$seed)], 'approved', $seed);
            }

            $this->actingAs($organizer)
                ->post(route('organizer.tournaments.draws.generate', [$tournament, $category]))
                ->assertRedirect();

            $this->assertSame($expectedMatches, $category->matches()->count(), "Unexpected match count for {$entrantCount} entrants.");
        }
    }

    public function test_single_elimination_byes_go_to_top_seeds_and_render_without_bye_vs_bye(): void
    {
        $organizer = $this->player('Seeded Bye Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Seeded Bye Singles', 'singles');

        foreach (range(1, 5) as $seed) {
            $this->entrant($tournament, $category, [$this->player('Seeded Bye Player '.$seed)], 'approved', $seed);
        }

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate', [$tournament, $category]), [
                'courts_count' => 2,
                'court_label_prefix' => 'Arena',
                'first_court_number' => 1,
                'schedule_start_time' => '09:00',
                'match_duration_minutes' => 30,
            ])
            ->assertRedirect();

        $category->refresh();

        $this->assertSame(1, $category->matches()->count());
        $this->assertSame([1, 3, 5], $category->entrants()->whereIn('seed', [1, 2, 3])->orderBy('seed')->pluck('draw_position')->all());
        $this->assertSame([7, 8], $category->entrants()->whereIn('seed', [4, 5])->orderBy('seed')->pluck('draw_position')->all());
        $this->assertSame(['Arena 1'], $category->matches()->pluck('court_label')->all());

        $this->get(route('tournaments.draw', [$tournament, $category]))
            ->assertOk()
            ->assertSee('8 draw')
            ->assertSeeInOrder([
                'Seeded Bye Player 1',
                'BYE',
                'Seeded Bye Player 2',
                'BYE',
                'Seeded Bye Player 3',
                'BYE',
                'Seeded Bye Player 4',
                'Seeded Bye Player 5',
                'Semifinals',
                'Seeded Bye Player 1',
                'Seeded Bye Player 2',
                'Seeded Bye Player 3',
                'Winner Match 4',
            ]);
    }

    public function test_round_robin_group_size_controls_grouping_and_pairings(): void
    {
        $organizer = $this->player('Group Size Owner');
        $tournament = $this->tournament($organizer);
        $cases = [
            3 => ['counts' => [3, 3, 2], 'matches' => 7],
            4 => ['counts' => [4, 4], 'matches' => 12],
            5 => ['counts' => [5, 5, 2], 'matches' => 21, 'entrants' => 12],
        ];

        foreach ($cases as $groupSize => $expected) {
            $category = $this->category($tournament, 'Group Size '.$groupSize, 'singles', 'round_robin', $groupSize);
            $entrantCount = $expected['entrants'] ?? 8;

            foreach (range(1, $entrantCount) as $seed) {
                $this->entrant($tournament, $category, [$this->player('Group Size '.$groupSize.' Player '.$seed)], 'approved', $seed);
            }

            $this->actingAs($organizer)
                ->post(route('organizer.tournaments.draws.generate', [$tournament, $category]), [
                    'courts_count' => 2,
                    'schedule_start_time' => '09:00',
                    'match_duration_minutes' => 30,
                ])
                ->assertRedirect();

            $this->assertSame($expected['matches'], $category->matches()->count());
            $this->assertSame($expected['counts'], $category->entrants()->orderBy('group_name')->get()->groupBy('group_name')->map->count()->values()->all());
        }
    }

    public function test_round_robin_group_pages_show_standings_and_group_matches(): void
    {
        $organizer = $this->player('Group Page Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'BWF Group Singles', 'singles', 'round_robin', 3);

        foreach (range(1, 6) as $seed) {
            $this->entrant($tournament, $category, [$this->player('BWF Group Player '.$seed)], 'approved', $seed);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->where('draw_group', 'Group A')->orderBy('draw_position')->firstOrFail();

        $this->actingAs($organizer)
            ->patch(route('organizer.tournaments.matches.result', [$tournament, $match]), [
                'played_at' => now()->toDateString(),
                'winner_side' => 'A',
                'games' => [
                    ['a' => 21, 'b' => 10],
                    ['a' => 21, 'b' => 12],
                ],
            ])
            ->assertRedirect();

        $this->get(route('tournaments.draw', [$tournament, $category]))
            ->assertOk()
            ->assertSee('Round robin groups of 3')
            ->assertSee('Group A')
            ->assertSee('Group B')
            ->assertSee('Standings')
            ->assertSee('Matches');

        $this->get(route('tournaments.draw.group', [$tournament, $category, 'group-a']))
            ->assertOk()
            ->assertSee('Group standings')
            ->assertSee('BWF Group Player 1')
            ->assertSeeInOrder(['BWF Group Player 1', 'BWF Group Player 2']);

        $this->get(route('tournaments.draw.group.matches', [$tournament, $category, 'group-a']))
            ->assertOk()
            ->assertSee('Group fixtures')
            ->assertSee('21-10, 21-12')
            ->assertSee('BWF Group Player 1')
            ->assertDontSee('BWF Group Player 4');
    }

    public function test_tournament_scoresheet_link_opens_by_secret_token(): void
    {
        $organizer = $this->player('Sheet Link Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Sheet Singles', 'singles');

        foreach (collect(range(1, 2))->map(fn ($i) => $this->player('Sheet Link Player '.$i)) as $index => $player) {
            $this->entrant($tournament, $category, [$player], 'approved', $index + 1);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->firstOrFail();

        $this->assertNotEmpty($match->score_sheet_token);
        $this->get(route('scoresheets.show', $match->score_sheet_token))->assertOk()->assertSee('Umpire scoresheet');
        $this->get('/s/notreal')->assertNotFound();

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.matches', $tournament))
            ->assertOk()
            ->assertSee('Score sheet');
    }

    public function test_scoresheet_tracks_live_points_and_submits_for_organizer_review(): void
    {
        $organizer = $this->player('Live Sheet Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Live Singles', 'singles');

        foreach (collect(range(1, 2))->map(fn ($i) => $this->player('Live Sheet Player '.$i)) as $index => $player) {
            $this->entrant($tournament, $category, [$player], 'approved', $index + 1);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->firstOrFail();

        $component = Livewire::test('scoresheets.show', ['token' => $match->score_sheet_token])
            ->call('addPoint', 'A')
            ->call('addPoint', 'B')
            ->call('undo');

        $this->assertSame(['a' => 1, 'b' => 0], $match->fresh()->live_score['current']);
        $this->get(route('tournaments.matches', $tournament))->assertOk()->assertSee('Live')->assertSee('A 1 - 0 B');

        $this->scoreGame($component, 20, 19);
        $component->call('endGame');
        $this->get(route('tournaments.matches', $tournament))
            ->assertOk()
            ->assertSee('Current game 2')
            ->assertSee('Completed game 1')
            ->assertSee('21 - 19');

        $this->scoreGame($component, 18, 21);
        $component->call('endGame');
        $this->scoreGame($component, 21, 15);
        $component->call('submitScore')->assertHasNoErrors();

        $match->refresh();

        $this->assertSame('submitted', $match->live_status);
        $this->assertSame('pending_confirmation', $match->status);
        $this->assertSame([], $match->score);
        $this->assertSame(0, RatingEvent::where('match_id', $match->id)->count());
        $this->assertSame([
            ['a' => 21, 'b' => 19],
            ['a' => 18, 'b' => 21],
            ['a' => 21, 'b' => 15],
        ], $match->live_score['games']);
    }

    public function test_organizer_approves_submitted_scoresheet_before_ratings_update(): void
    {
        $organizer = $this->player('Approve Sheet Owner');
        $other = $this->player('Approve Sheet Other');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Approve Singles', 'singles');

        foreach (collect(range(1, 2))->map(fn ($i) => $this->player('Approve Sheet Player '.$i)) as $index => $player) {
            $this->entrant($tournament, $category, [$player], 'approved', $index + 1);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->firstOrFail();
        $match->forceFill([
            'live_status' => 'submitted',
            'live_score' => [
                'current_game' => 4,
                'current' => ['a' => 0, 'b' => 0],
                'games' => [
                    ['a' => 21, 'b' => 17],
                    ['a' => 17, 'b' => 21],
                    ['a' => 21, 'b' => 18],
                ],
                'history' => [],
            ],
            'score_submitted_at' => now(),
        ])->save();

        $this->actingAs($other)
            ->patch(route('organizer.tournaments.matches.approve-live-score', [$tournament, $match]))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->patch(route('organizer.tournaments.matches.approve-live-score', [$tournament, $match]))
            ->assertRedirect()
            ->assertSessionHas('status', 'Live scoresheet approved and ratings updated.');

        $match->refresh();

        $this->assertSame('confirmed', $match->status);
        $this->assertSame('approved', $match->live_status);
        $this->assertSame('A', $match->winner_side);
        $this->assertSame([
            ['a' => 21, 'b' => 17],
            ['a' => 17, 'b' => 21],
            ['a' => 21, 'b' => 18],
        ], $match->score);
        $this->assertSame(2, RatingEvent::where('match_id', $match->id)->count());
    }

    public function test_public_tournament_matches_show_live_matches_first(): void
    {
        $organizer = $this->player('Live Priority Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Priority Singles', 'singles');

        foreach (range(1, 4) as $index) {
            $this->entrant($tournament, $category, [$this->player('Live Priority Player '.$index)], 'approved', $index);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));

        $matches = MatchRecord::where('tournament_category_id', $category->id)
            ->orderBy('draw_position')
            ->get();

        $matches->last()->forceFill([
            'live_status' => 'live',
            'live_score' => [
                'current_game' => 1,
                'current' => ['a' => 3, 'b' => 2],
                'games' => [],
                'history' => [],
            ],
        ])->save();

        $this->get(route('tournaments.matches', $tournament))
            ->assertOk()
            ->assertSeeInOrder([
                'Live matches',
                'A: Live Priority Player 3',
                'A: Live Priority Player 1',
            ]);
    }

    public function test_draw_generation_uses_category_from_the_selected_tournament(): void
    {
        $otherOrganizer = $this->player('Other Draw Owner');
        $otherTournament = $this->tournament($otherOrganizer);
        $otherTournament->categories()->create([
            'name' => 'Amateur Doubles',
            'slug' => 'amateur-doubles',
            'format' => 'doubles',
            'draw_mode' => 'round_robin',
            'status' => 'published',
        ]);

        $organizer = $this->player('Scoped Draw Owner');
        $tournament = $this->tournament($organizer);
        $category = $tournament->categories()->create([
            'name' => 'Amateur Doubles',
            'slug' => 'amateur-doubles',
            'format' => 'doubles',
            'draw_mode' => 'round_robin',
            'status' => 'published',
        ]);

        $players = collect(range(1, 8))->map(fn ($i) => $this->player('Scoped Draw Player '.$i));
        foreach ($players->chunk(2)->values() as $index => $pair) {
            $this->entrant($tournament, $category, $pair->values()->all(), 'approved', $index + 1);
        }

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate', [$tournament, $category]))
            ->assertRedirect();

        $this->assertSame(6, $category->matches()->count());
    }

    public function test_draw_generation_requires_two_approved_entrants(): void
    {
        $organizer = $this->player('Empty Draw Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Empty Singles', 'singles');

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate', [$tournament, $category]))
            ->assertSessionHasErrors('draw');

        $this->assertSame(0, $category->matches()->count());
    }

    public function test_direct_generate_draw_url_redirects_to_draws_page(): void
    {
        $organizer = $this->player('Direct Draw Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Direct Singles', 'singles');

        $this->actingAs($organizer)
            ->get(route('organizer.tournaments.draws.generate.notice', [$tournament, $category]))
            ->assertRedirect(route('organizer.tournaments.draws', $tournament))
            ->assertSessionHasErrors('draw');
    }

    public function test_organizer_can_submit_tournament_match_result(): void
    {
        $organizer = $this->player('Result Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Result Doubles', 'doubles', 'round_robin');

        $players = collect(range(1, 4))->map(fn ($i) => $this->player('Result Player '.$i));
        foreach ($players->chunk(2)->values() as $index => $pair) {
            $this->entrant($tournament, $category, $pair->values()->all(), 'approved', $index + 1);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->firstOrFail();

        $this->actingAs($organizer)
            ->patch(route('organizer.tournaments.matches.result', [$tournament, $match]), [
                'played_at' => now()->toDateString(),
                'winner_side' => 'B',
                'games' => [
                    ['a' => 21, 'b' => 19],
                    ['a' => 18, 'b' => 21],
                    ['a' => 16, 'b' => 21],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Tournament match result saved and ratings updated.');

        $match->refresh();

        $this->assertSame('confirmed', $match->status);
        $this->assertSame('B', $match->winner_side);
        $this->assertSame([
            ['a' => 21, 'b' => 19],
            ['a' => 18, 'b' => 21],
            ['a' => 16, 'b' => 21],
        ], $match->score);
        $this->assertSame(4, RatingEvent::where('match_id', $match->id)->count());
    }

    public function test_tournament_match_result_requires_scores_that_match_winner(): void
    {
        $organizer = $this->player('Invalid Result Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Invalid Singles', 'singles');

        $players = collect(range(1, 2))->map(fn ($i) => $this->player('Invalid Result Player '.$i));
        foreach ($players as $index => $player) {
            $this->entrant($tournament, $category, [$player], 'approved', $index + 1);
        }

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));
        $match = $category->matches()->firstOrFail();

        $this->actingAs($organizer)
            ->patch(route('organizer.tournaments.matches.result', [$tournament, $match]), [
                'played_at' => now()->toDateString(),
                'winner_side' => 'B',
                'games' => [
                    ['a' => 21, 'b' => 18],
                    ['a' => 21, 'b' => 19],
                ],
            ])
            ->assertSessionHasErrors('result');

        $this->assertSame('pending_confirmation', $match->fresh()->status);
        $this->assertSame(0, RatingEvent::where('match_id', $match->id)->count());
    }

    public function test_public_tournament_pages_render(): void
    {
        $organizer = $this->player('Public Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Open Singles', 'singles');
        $entrantA = $this->entrant($tournament, $category, [$this->player('Public A')], 'approved');
        $entrantB = $this->entrant($tournament, $category, [$this->player('Public B')], 'approved');
        $this->entrant($tournament, $category, [$this->player('Public C')], 'approved', 3);
        $this->entrant($tournament, $category, [$this->player('Public D')], 'approved', 4);

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Open Singles')
            ->assertSee($entrantA->players->first()->user->name)
            ->assertDontSee('Live now')
            ->assertDontSee('Live Scores');
        $this->get(route('tournaments.draw', [$tournament, $category]))
            ->assertOk()
            ->assertSee('Open Singles draw')
            ->assertSee('Semifinals')
            ->assertSee('Final')
            ->assertSee('Winner Match 1');
        $this->get(route('tournaments.matches', $tournament))
            ->assertOk()
            ->assertSee('Matches')
            ->assertSee('Public A')
            ->assertSee('List')
            ->assertSee('Grid')
            ->assertDontSee('Tournament schedule')
            ->assertDontSee('Filter date');
    }

    public function test_tournament_overview_signals_live_matches_without_live_nav_item(): void
    {
        $organizer = $this->player('Overview Live Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Overview Live Singles', 'singles');
        $opponent = $this->player('Overview Live Opponent');

        $this->scheduledTournamentMatch($tournament, $category, $this->player('Overview Live Player'), $opponent, now()->toDateString(), 'Court Live', 'live');

        $this->get(route('tournaments.show', $tournament))
            ->assertOk()
            ->assertSee('Live now')
            ->assertSee('1 match in progress')
            ->assertSee('Court Live')
            ->assertSee('View live matches')
            ->assertSee('#live', false)
            ->assertDontSee('Live Scores');
    }

    public function test_public_tournament_players_page_lists_approved_players_and_searches_names_or_schools(): void
    {
        $organizer = $this->player('Players Page Owner');
        $tournament = $this->tournament($organizer);
        $singles = $this->category($tournament, 'Youth Singles', 'singles');
        $doubles = $this->category($tournament, 'Youth Doubles', 'doubles');
        $alpha = $this->player('Alpha Player');
        $beta = $this->player('Beta Player');
        $hidden = $this->player('Hidden Player');
        $alpha->playerProfile->forceFill(['gender' => 'male'])->save();
        $beta->playerProfile->forceFill(['gender' => 'female'])->save();

        $this->entrant($tournament, $singles, [$alpha], 'approved', 1, ['Alpha School']);
        $this->entrant($tournament, $doubles, [$alpha, $this->player('Alpha Partner')], 'approved', 2, ['Alpha School', 'Partner School']);
        $this->entrant($tournament, $singles, [$beta], 'approved', null, ['Beta Academy']);
        $this->entrant($tournament, $singles, [$hidden], 'pending', null, ['Hidden School']);

        $this->get(route('tournaments.players', $tournament))
            ->assertOk()
            ->assertSee('Players')
            ->assertSee('Alpha Player')
            ->assertSee('Youth Singles')
            ->assertSee('Youth Doubles')
            ->assertDontSee('Hidden Player');

        Livewire::test('tournaments.players', ['tournament' => $tournament])
            ->assertSee('Alpha Player')
            ->assertSee('Beta Player')
            ->assertDontSee('Hidden Player')
            ->set('search', 'Alpha School')
            ->assertSee('Alpha Player')
            ->assertDontSee('Beta Player')
            ->set('search', 'beta academy')
            ->assertSee('Beta Player')
            ->assertDontSee('Alpha Player')
            ->set('search', '')
            ->set('gender', 'female')
            ->assertSee('Beta Player')
            ->assertDontSee('Alpha Player')
            ->set('gender', '')
            ->set('category', (string) $doubles->id)
            ->assertSee('Youth Doubles')
            ->assertSee('Alpha Player')
            ->assertDontSee('Beta Player');
    }

    public function test_tournament_draw_search_filters_by_player_or_school(): void
    {
        $organizer = $this->player('Draw Search Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Round Robin Singles', 'singles', 'round_robin', 3);
        $bracketCategory = $this->category($tournament, 'Bracket Singles', 'singles');
        $alpha = $this->player('Draw Alpha');
        $beta = $this->player('Draw Beta');
        $gamma = $this->player('Draw Gamma');
        $delta = $this->player('Draw Delta');

        $this->entrant($tournament, $category, [$alpha], 'approved', 1, ['Alpha School']);
        $this->entrant($tournament, $category, [$beta], 'approved', 2, ['Beta Academy']);
        $this->entrant($tournament, $category, [$gamma], 'approved', 3, ['Gamma Institute']);
        $this->entrant($tournament, $bracketCategory, [$alpha], 'approved', 1, ['Alpha School']);
        $this->entrant($tournament, $bracketCategory, [$beta], 'approved', 2, ['Beta Academy']);
        $this->entrant($tournament, $bracketCategory, [$gamma], 'approved', 3, ['Gamma Institute']);
        $this->entrant($tournament, $bracketCategory, [$delta], 'approved', 4, ['Delta College']);

        app(TournamentDrawService::class)->generate($category);
        app(TournamentDrawService::class)->generate($bracketCategory);

        Livewire::test('tournaments.draw', ['tournament' => $tournament, 'category' => $category])
            ->assertSee('Draw Alpha')
            ->assertSee('Draw Beta')
            ->set('search', 'gamma institute')
            ->assertSee('Draw Gamma')
            ->assertDontSee('Draw Alpha')
            ->assertDontSee('Draw Beta');

        Livewire::test('tournaments.draw', ['tournament' => $tournament, 'category' => $bracketCategory])
            ->set('search', 'gamma institute')
            ->assertSee('Draw Gamma')
            ->assertDontSee('Draw Alpha')
            ->assertDontSee('Draw Beta');
    }

    public function test_mixed_tournament_categories_generate_mixed_matches(): void
    {
        $organizer = $this->player('Mixed Draw Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Mixed Under 18', 'mixed');
        [$a1, $a2, $b1, $b2] = [
            $this->player('Tournament Mixed A One', 'male'),
            $this->player('Tournament Mixed A Two', 'female'),
            $this->player('Tournament Mixed B One', 'male'),
            $this->player('Tournament Mixed B Two', 'female'),
        ];

        $this->entrant($tournament, $category, [$a1, $a2], 'approved', 1);
        $this->entrant($tournament, $category, [$b1, $b2], 'approved', 2);

        app(TournamentDrawService::class)->generate($category);

        $match = $category->matches()->firstOrFail();

        $this->assertSame('mixed', $match->format);
        $this->assertSame(4, $match->players()->count());
    }

    public function test_tournament_matches_search_filters_by_player_or_school_and_preserves_date_filter(): void
    {
        $organizer = $this->player('Matches Search Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Match Search Singles', 'singles', 'round_robin', 3);
        $alpha = $this->player('Match Alpha');
        $beta = $this->player('Match Beta');
        $gamma = $this->player('Match Gamma');

        $this->entrant($tournament, $category, [$alpha], 'approved', 1, ['Alpha School']);
        $this->entrant($tournament, $category, [$beta], 'approved', 2, ['Beta Academy']);
        $this->entrant($tournament, $category, [$gamma], 'approved', 3, ['Gamma Institute']);

        app(TournamentDrawService::class)->generate($category, [
            'schedule_start_time' => '09:00',
            'match_duration_minutes' => 30,
        ]);

        $firstMatch = $category->matches()->with('players.user')->orderBy('id')->firstOrFail();
        $category->matches()
            ->whereKeyNot($firstMatch->id)
            ->get()
            ->each(fn (MatchRecord $match) => $match->forceFill([
                'played_at' => now()->addDays(30)->toDateString(),
                'scheduled_at' => now()->addDays(30)->setTime(9, 0),
            ])->save());
        $firstMatchPlayers = $firstMatch->players->pluck('user.name')->all();

        Livewire::test('tournaments.matches', ['tournament' => $tournament, 'date' => $firstMatch->played_at->toDateString()])
            ->assertSee($firstMatchPlayers[0])
            ->assertDontSee('Match Gamma')
            ->set('search', 'alpha school')
            ->assertSee('Match Alpha')
            ->assertDontSee('Match Gamma');
    }

    public function test_tournament_matches_use_real_date_tabs_defaults_and_grid_view(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-13 10:00:00'));

        try {
            $organizer = $this->player('Date Tabs Owner');
            $tournament = $this->tournament($organizer);
            $category = $this->category($tournament, 'Date Tabs Singles', 'singles');
            $opponent = $this->player('Date Tabs Opponent');

            $pastMatch = $this->scheduledTournamentMatch($tournament, $category, $this->player('Past Date Player'), $opponent, '2026-05-12', 'Court 1');
            $todayMatch = $this->scheduledTournamentMatch($tournament, $category, $this->player('Today Date Player'), $opponent, '2026-05-13', 'Court 2');
            $futureMatch = $this->scheduledTournamentMatch($tournament, $category, $this->player('Future Date Player'), $opponent, '2026-05-15', 'Court 3');
            $liveMatch = $this->scheduledTournamentMatch($tournament, $category, $this->player('Live Remote Player'), $opponent, '2026-05-12', 'Court Live', 'live');

            Livewire::test('tournaments.matches', ['tournament' => $tournament, 'date' => '2026-05-14'])
                ->assertSee('May 12')
                ->assertSee('May 13')
                ->assertSee('May 15')
                ->assertSee('Today Date Player')
                ->assertSee('Live Remote Player')
                ->assertDontSee('Past Date Player')
                ->assertDontSee('Future Date Player');

            Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

            Livewire::test('tournaments.matches', ['tournament' => $tournament])
                ->assertSee('Past Date Player')
                ->assertDontSee('Today Date Player')
                ->assertDontSee('Future Date Player');

            Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00'));

            Livewire::test('tournaments.matches', ['tournament' => $tournament, 'view' => 'grid'])
                ->assertSee('Grid view')
                ->assertSee('Court 3')
                ->assertSee('Future Date Player')
                ->assertDontSee('Past Date Player')
                ->assertDontSee('Today Date Player');

            $this->assertSame('2026-05-12', $pastMatch->played_at->toDateString());
            $this->assertSame('2026-05-13', $todayMatch->played_at->toDateString());
            $this->assertSame('2026-05-15', $futureMatch->played_at->toDateString());
            $this->assertSame('live', $liveMatch->live_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function tournament(User $organizer): Tournament
    {
        return Tournament::create([
            'organizer_id' => $organizer->id,
            'name' => 'Test Tournament '.$organizer->id,
            'slug' => 'test-tournament-'.$organizer->id,
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

    private function category(Tournament $tournament, string $name, string $format, string $drawMode = 'single_elimination', int $groupSize = 4): TournamentCategory
    {
        return $tournament->categories()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(100, 999),
            'format' => $format,
            'draw_mode' => $drawMode,
            'group_size' => $groupSize,
            'status' => 'published',
        ]);
    }

    private function entrant(Tournament $tournament, TournamentCategory $category, array $players, string $status, ?int $seed = null, array $schoolNames = []): TournamentEntrant
    {
        $entrant = $tournament->entrants()->create([
            'tournament_category_id' => $category->id,
            'created_by' => $players[0]->id,
            'status' => $status,
            'seed' => $seed,
        ]);

        foreach ($players as $index => $player) {
            $entrant->players()->create([
                'user_id' => $player->id,
                'school_name' => $schoolNames[$index] ?? null,
                'position' => $index + 1,
            ]);
        }

        return $entrant->load('players.user.playerProfile');
    }

    private function player(string $name, ?string $gender = null): User
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
            'gender' => $gender,
        ]);

        return $user->load('playerProfile');
    }

    private function scheduledTournamentMatch(Tournament $tournament, TournamentCategory $category, User $sideA, User $sideB, string $date, string $court, string $liveStatus = 'scheduled'): MatchRecord
    {
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $sideA->id,
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'status' => 'pending_confirmation',
            'played_at' => $date,
            'scheduled_at' => Carbon::parse($date.' 09:00:00'),
            'court_label' => $court,
            'score' => [],
            'winner_side' => 'A',
            'live_status' => $liveStatus,
        ]);

        $match->players()->create(['user_id' => $sideA->id, 'side' => 'A', 'position' => 1]);
        $match->players()->create(['user_id' => $sideB->id, 'side' => 'B', 'position' => 1]);

        return $match;
    }

    private function scoreGame($component, int $sideA, int $sideB): void
    {
        for ($i = 0; $i < $sideA; $i++) {
            $component->call('addPoint', 'A');
        }

        for ($i = 0; $i < $sideB; $i++) {
            $component->call('addPoint', 'B');
        }
    }
}
