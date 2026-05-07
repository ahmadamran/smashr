<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Players\Models\PlayerProfile;
use Modules\Tournaments\Models\Tournament;
use Modules\Tournaments\Models\TournamentCategory;
use Modules\Tournaments\Models\TournamentEntrant;
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

        $this->actingAs($player)
            ->post(route('tournaments.register', $tournament), ['tournament_category_id' => $category->id])
            ->assertRedirect();

        $this->assertDatabaseHas('tournament_entrants', [
            'tournament_id' => $tournament->id,
            'tournament_category_id' => $category->id,
            'status' => 'pending',
        ]);

        $tournament->forceFill(['registration_mode' => 'private'])->save();

        $this->actingAs($player)
            ->post(route('tournaments.register', $tournament), ['tournament_category_id' => $category->id])
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
            ->post(route('organizer.tournaments.draws.generate', [$tournament, $knockout]))
            ->assertRedirect();
        $this->assertSame(2, $knockout->matches()->count());

        $this->actingAs($organizer)
            ->post(route('organizer.tournaments.draws.generate', [$tournament, $roundRobin]))
            ->assertRedirect();
        $this->assertSame(6, $roundRobin->matches()->count());
    }

    public function test_public_tournament_pages_render(): void
    {
        $organizer = $this->player('Public Owner');
        $tournament = $this->tournament($organizer);
        $category = $this->category($tournament, 'Open Singles', 'singles');
        $entrantA = $this->entrant($tournament, $category, [$this->player('Public A')], 'approved');
        $entrantB = $this->entrant($tournament, $category, [$this->player('Public B')], 'approved');

        $this->actingAs($organizer)->post(route('organizer.tournaments.draws.generate', [$tournament, $category]));

        $this->get(route('tournaments.show', $tournament))->assertOk()->assertSee('Open Singles')->assertSee($entrantA->players->first()->user->name);
        $this->get(route('tournaments.draw', [$tournament, $category]))->assertOk()->assertSee('Open Singles draw');
        $this->get(route('tournaments.matches', $tournament))->assertOk()->assertSee('Tournament matches')->assertSee('Public A');
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

    private function category(Tournament $tournament, string $name, string $format, string $drawMode = 'single_elimination'): TournamentCategory
    {
        return $tournament->categories()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.fake()->unique()->numberBetween(100, 999),
            'format' => $format,
            'draw_mode' => $drawMode,
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
