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
