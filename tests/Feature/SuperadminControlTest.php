<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;
use Modules\Ratings\Services\RatingRecalculationService;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
use Modules\Tournaments\Models\TournamentEntrantPlayer;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SuperadminControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_and_regular_user_cannot_access_admin_but_superadmin_can(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));

        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Control centre');
    }

    public function test_database_seeder_creates_superadmin_account_and_active_algorithm(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@smashr.test')->firstOrFail();

        $this->assertTrue($admin->hasRole('superadmin'));
        $this->assertTrue(RatingAlgorithm::where('version', 'v1')->where('status', 'active')->exists());
        $this->assertSame([
            'kejohanan-badminton-mssj-2026',
            'mss-melaka-badminton-2026',
        ], Tournament::orderBy('slug')->pluck('slug')->all());
        $this->assertFalse(PlayerProfile::where('user_id', $admin->id)->exists());
    }

    public function test_match_context_can_attach_club_and_tournament(): void
    {
        $club = Club::create(['name' => 'Context Club', 'slug' => 'context-club']);
        $tournament = Tournament::create([
            'club_id' => $club->id,
            'name' => 'Context Open',
            'slug' => 'context-open',
            'status' => 'published',
        ]);

        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $this->player('Submitter')->id,
            'club_id' => $club->id,
            'tournament_id' => $tournament->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 15], ['a' => 21, 'b' => 17]],
            'winner_side' => 'A',
        ]);

        $this->assertTrue($match->club->is($club));
        $this->assertTrue($match->tournament->is($tournament));
    }

    public function test_active_algorithm_is_used_for_future_matches_and_old_events_keep_algorithm_id(): void
    {
        $v1 = $this->algorithm('v1', 'active', ['base_delta' => 0.180]);
        [$a, $b, $c] = [$this->player('Alpha'), $this->player('Bravo'), $this->player('Charlie')];

        $firstMatch = $this->confirmedSinglesMatch($a, $b);
        $firstEvent = RatingEvent::where('match_id', $firstMatch->id)->firstOrFail();

        $this->assertSame($v1->id, $firstEvent->rating_algorithm_id);

        $v2 = $this->algorithm('v2', 'draft', ['base_delta' => 0.300]);
        RatingAlgorithm::where('status', 'active')->update(['status' => 'archived']);
        $v2->forceFill(['status' => 'active', 'activated_at' => now()])->save();

        $secondMatch = $this->confirmedSinglesMatch($a->fresh(), $c);
        $secondEvent = RatingEvent::where('match_id', $secondMatch->id)->firstOrFail();

        $this->assertSame($v1->id, $firstEvent->fresh()->rating_algorithm_id);
        $this->assertSame($v2->id, $secondEvent->rating_algorithm_id);
    }

    public function test_recalculation_preview_does_not_mutate_and_apply_rebuilds_with_selected_algorithm(): void
    {
        $this->algorithm('v1', 'active', ['base_delta' => 0.180]);
        [$winner, $loser] = [$this->player('Recalc Winner'), $this->player('Recalc Loser')];

        $match = $this->confirmedSinglesMatch($winner, $loser);
        $beforePreview = (string) $winner->playerProfile->fresh()->singles_rating;

        $v2 = $this->algorithm('v2', 'draft', ['base_delta' => 0.350]);
        $preview = app(RatingRecalculationService::class)->preview($v2);

        $this->assertSame(1, $preview['matches']);
        $this->assertSame($beforePreview, (string) $winner->playerProfile->fresh()->singles_rating);

        app(RatingRecalculationService::class)->apply($v2);

        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertNotSame($beforePreview, (string) $winner->playerProfile->fresh()->singles_rating);
        $this->assertSame(2, RatingEvent::where('match_id', $match->id)->where('rating_algorithm_id', $v2->id)->count());
    }

    public function test_superadmin_can_suspend_and_reactivate_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $user), ['suspended' => true])
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->suspended_at);

        $this->actingAs($admin)
            ->patch(route('admin.users.suspension', $user), ['suspended' => false])
            ->assertRedirect();

        $this->assertNull($user->fresh()->suspended_at);
    }

    public function test_superadmin_can_update_draft_algorithm_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $algorithm = $this->algorithm('v2', 'draft');

        $settings = array_replace(RatingAlgorithm::DEFAULT_SETTINGS, ['base_delta' => 0.420]);

        $this->actingAs($admin)
            ->patch(route('admin.algorithms.update', $algorithm), [
                'name' => 'Smashr v2 tuned',
                'version' => 'v2-tuned',
                'settings' => $settings,
            ])
            ->assertRedirect();

        $this->assertSame('v2-tuned', $algorithm->fresh()->version);
        $this->assertSame(0.420, (float) $algorithm->fresh()->settings['base_delta']);
    }

    public function test_admin_crud_pages_render_as_table_based_screens(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $club = Club::create(['name' => 'Admin Club', 'slug' => 'admin-club']);
        $tournament = Tournament::create(['club_id' => $club->id, 'organizer_id' => $admin->id, 'name' => 'Admin Open', 'slug' => 'admin-open', 'status' => 'published']);
        $player = $this->player('Admin Page Player');
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $player->id,
            'tournament_id' => $tournament->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
        ]);
        $algorithm = $this->algorithm('admin-v2', 'draft');

        foreach ([
            route('admin.users'),
            route('admin.users.create'),
            route('admin.users.show', $player),
            route('admin.users.edit', $player),
            route('admin.clubs'),
            route('admin.clubs.create'),
            route('admin.clubs.show', $club),
            route('admin.clubs.edit', $club),
            route('admin.tournaments'),
            route('admin.tournaments.create'),
            route('admin.tournaments.show', $tournament),
            route('admin.tournaments.edit', $tournament),
            route('admin.matches'),
            route('admin.matches.create'),
            route('admin.matches.show', $match),
            route('admin.matches.edit', $match),
            route('admin.algorithms'),
            route('admin.algorithms.create'),
            route('admin.algorithms.edit', $algorithm),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_user_crud_and_smashr_points_adjustment(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Managed User',
                'email' => 'managed@example.com',
                'password' => 'password123',
                'phone_number' => '+60123456789',
                'country' => 'Malaysia',
                'smashr_points' => 15,
            ])
            ->assertRedirect(route('admin.users'));

        $user = User::where('email', 'managed@example.com')->firstOrFail();
        $this->assertSame(15, $user->playerProfile->smashr_points);
        $this->assertSame('+60123456789', $user->playerProfile->phone_number);
        $this->assertSame('Malaysia', $user->playerProfile->country);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $user), [
                'name' => 'Managed User Updated',
                'email' => 'managed-updated@example.com',
                'phone_number' => '+6588887777',
                'country' => 'Singapore',
            ])
            ->assertRedirect(route('admin.users'));

        $this->assertSame('+6588887777', $user->playerProfile->fresh()->phone_number);
        $this->assertSame('Singapore', $user->playerProfile->fresh()->country);

        $this->actingAs($admin)
            ->post(route('admin.users.points', $user), ['mode' => 'add', 'points' => 20, 'reason' => 'Manual admin bonus'])
            ->assertRedirect();

        $this->assertSame(35, $user->playerProfile->fresh()->smashr_points);
        $this->assertDatabaseHas('smashr_point_adjustments', ['user_id' => $user->id, 'before_points' => 15, 'after_points' => 35]);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_superadmin_can_merge_duplicate_clubs_into_keeper(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $keeper = Club::create(['name' => 'Keeper Club', 'slug' => 'keeper-club']);
        $duplicate = Club::create(['name' => 'Keeper Clb', 'slug' => 'keeper-clb']);
        $member = $this->player('Duplicate Club Member');
        $duplicate->members()->attach($member->id);
        $tournament = Tournament::create(['club_id' => $duplicate->id, 'name' => 'Club Merge Open', 'slug' => 'club-merge-open', 'status' => 'published']);
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $member->id,
            'club_id' => $duplicate->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.clubs.merge', $keeper), ['source_ids' => (string) $duplicate->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('clubs', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('club_player', ['club_id' => $keeper->id, 'user_id' => $member->id]);
        $this->assertSame($keeper->id, $tournament->fresh()->club_id);
        $this->assertSame($keeper->id, $match->fresh()->club_id);
    }

    public function test_superadmin_can_merge_duplicate_users_into_keeper(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $keeper = $this->player('Keeper Player');
        $duplicate = $this->player('Keeper Plyer');
        $club = Club::create(['name' => 'Merge User Club', 'slug' => 'merge-user-club']);
        $club->members()->attach($duplicate->id);
        $algorithm = $this->algorithm('merge-v1', 'active');
        $tournament = Tournament::create([
            'organizer_id' => $duplicate->id,
            'name' => 'User Merge Open',
            'slug' => 'user-merge-open',
            'status' => 'published',
        ]);
        $category = TournamentCategory::create([
            'tournament_id' => $tournament->id,
            'name' => 'Boys Singles',
            'slug' => 'boys-singles',
            'format' => 'singles',
        ]);
        $entrant = TournamentEntrant::create([
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'created_by' => $duplicate->id,
            'status' => 'approved',
        ]);
        TournamentEntrantPlayer::create([
            'tournament_entrant_id' => $entrant->id,
            'user_id' => $duplicate->id,
            'position' => 1,
        ]);
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $duplicate->id,
            'tournament_id' => $tournament->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $duplicate->id, 'side' => 'A', 'position' => 1]);
        RatingEvent::create([
            'match_id' => $match->id,
            'rating_algorithm_id' => $algorithm->id,
            'user_id' => $duplicate->id,
            'format' => 'singles',
            'rating_before' => 3.500,
            'rating_after' => 3.680,
            'delta' => 0.180,
            'reason' => 'win',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.merge', $keeper), ['source_ids' => (string) $duplicate->id])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('club_player', ['club_id' => $club->id, 'user_id' => $keeper->id]);
        $this->assertSame($keeper->id, $tournament->fresh()->organizer_id);
        $this->assertSame($keeper->id, $entrant->fresh()->created_by);
        $this->assertDatabaseHas('tournament_entrant_players', ['tournament_entrant_id' => $entrant->id, 'user_id' => $keeper->id]);
        $this->assertSame($keeper->id, $match->fresh()->submitted_by);
        $this->assertDatabaseHas('match_players', ['match_id' => $match->id, 'user_id' => $keeper->id]);
        $this->assertDatabaseHas('rating_events', ['match_id' => $match->id, 'user_id' => $keeper->id]);
    }

    public function test_admin_algorithm_create_activate_duplicate_and_delete_draft(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $active = $this->algorithm('active-v1', 'active');
        $settings = RatingAlgorithm::DEFAULT_SETTINGS;

        $this->actingAs($admin)
            ->post(route('admin.algorithms.store'), ['name' => 'Next Algo', 'version' => 'next-v1', 'settings' => $settings])
            ->assertRedirect(route('admin.algorithms'));

        $draft = RatingAlgorithm::where('version', 'next-v1')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.algorithms.activate', $draft))->assertRedirect();

        $this->assertSame('active', $draft->fresh()->status);
        $this->assertSame('archived', $active->fresh()->status);

        $this->actingAs($admin)->post(route('admin.algorithms.duplicate', $draft))->assertRedirect();
        $copy = RatingAlgorithm::where('version', 'like', 'next-v1-copy-%')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.algorithms.destroy', $copy))->assertRedirect(route('admin.algorithms'));
        $this->assertDatabaseMissing('rating_algorithms', ['id' => $copy->id]);
    }

    public function test_admin_match_filtering_and_actions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $player = $this->player('Filter Winner');
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $player->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
            'court_label' => 'Court 8',
        ]);

        $this->actingAs($admin)->get(route('admin.matches', ['status' => 'pending_confirmation', 'court' => 'Court 8']))->assertOk()->assertSee('Court 8');
        $this->actingAs($admin)->patch(route('admin.matches.dispute', $match))->assertRedirect();
        $this->assertSame('disputed', $match->fresh()->status);
        $this->actingAs($admin)->patch(route('admin.matches.void', $match))->assertRedirect();
        $this->assertSame('void', $match->fresh()->status);
    }

    public function test_admin_can_create_match(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $sideA = $this->player('Create Match A');
        $sideB = $this->player('Create Match B');
        $club = Club::create(['name' => 'Create Match Club', 'slug' => 'create-match-club']);

        $this->actingAs($admin)
            ->post(route('admin.matches.store'), [
                'format' => 'singles',
                'club_id' => $club->id,
                'side_a_user_id' => $sideA->id,
                'side_b_user_id' => $sideB->id,
                'played_at' => now()->toDateString(),
                'court_label' => 'Court 2',
                'estimated_duration_minutes' => 30,
                'winner_side' => 'A',
                'status' => 'pending_confirmation',
            ])
            ->assertRedirect();

        $match = MatchRecord::where('court_label', 'Court 2')->firstOrFail();
        $this->assertSame('singles', $match->format);
        $this->assertSame(2, $match->players()->count());
        $this->assertDatabaseHas('match_players', ['match_id' => $match->id, 'user_id' => $sideA->id, 'side' => 'A']);
        $this->assertDatabaseHas('match_players', ['match_id' => $match->id, 'user_id' => $sideB->id, 'side' => 'B']);
    }

    public function test_admin_can_bulk_confirm_and_void_matches(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $this->algorithm('bulk-v1', 'active');
        [$a, $b, $c, $d] = [$this->player('Bulk A'), $this->player('Bulk B'), $this->player('Bulk C'), $this->player('Bulk D')];
        $first = $this->pendingSinglesMatch($a, $b);
        $second = $this->pendingSinglesMatch($c, $d);
        $third = $this->pendingSinglesMatch($a->fresh(), $c->fresh());

        $this->actingAs($admin)
            ->post(route('admin.matches.bulk'), ['action' => 'confirm', 'match_ids' => [$first->id, $second->id]])
            ->assertRedirect();

        $this->assertSame('confirmed', $first->fresh()->status);
        $this->assertSame('confirmed', $second->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.matches.bulk'), ['action' => 'void', 'match_ids' => [$third->id]])
            ->assertRedirect();

        $this->assertSame('void', $third->fresh()->status);
    }

    public function test_admin_can_apply_bulk_action_to_all_filtered_matches_across_pages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('superadmin', 'web'));
        $submitter = $this->player('Filtered Submitter');

        foreach (range(1, 22) as $index) {
            MatchRecord::create([
                'format' => 'singles',
                'submitted_by' => $submitter->id,
                'status' => 'pending_confirmation',
                'played_at' => now()->toDateString(),
                'score' => [],
                'winner_side' => 'A',
                'court_label' => 'Filtered Court',
            ]);
        }

        MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $submitter->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
            'court_label' => 'Other Court',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.matches.bulk'), [
                'action' => 'void',
                'all_filtered' => 1,
                'status' => 'pending_confirmation',
                'court' => 'Filtered Court',
            ])
            ->assertRedirect();

        $this->assertSame(22, MatchRecord::where('court_label', 'Filtered Court')->where('status', 'void')->count());
        $this->assertSame('pending_confirmation', MatchRecord::where('court_label', 'Other Court')->firstOrFail()->status);
    }

    private function algorithm(string $version, string $status, array $settings = []): RatingAlgorithm
    {
        return RatingAlgorithm::create([
            'name' => 'Smashr '.$version,
            'version' => $version,
            'status' => $status,
            'settings' => array_replace(RatingAlgorithm::DEFAULT_SETTINGS, $settings),
            'activated_at' => $status === 'active' ? now() : null,
        ]);
    }

    private function confirmedSinglesMatch(User $winner, User $loser): MatchRecord
    {
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $winner->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 12], ['a' => 21, 'b' => 16]],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $winner->id, 'side' => 'A', 'position' => 1, 'confirmed_at' => now()]);
        $match->players()->create(['user_id' => $loser->id, 'side' => 'B', 'position' => 1]);

        return app(RatingService::class)->confirmForUser($match, $loser->id);
    }

    private function pendingSinglesMatch(User $winner, User $loser): MatchRecord
    {
        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $winner->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 12], ['a' => 21, 'b' => 16]],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $winner->id, 'side' => 'A', 'position' => 1]);
        $match->players()->create(['user_id' => $loser->id, 'side' => 'B', 'position' => 1]);

        return $match;
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
