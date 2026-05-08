<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingEvent;
use Modules\Ratings\Services\RatingService;
use Tests\TestCase;

class SmashrPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_player_profile_and_club_membership(): void
    {
        Volt::test('pages.auth.register')
            ->set('first_name', 'Amran')
            ->set('last_name', 'Shuttler')
            ->set('email', 'amran@example.com')
            ->set('phone_number', '+60123456789')
            ->set('gender', 'male')
            ->set('birthdate', '1990-01-01')
            ->set('country', 'Malaysia')
            ->set('state', 'Selangor')
            ->set('city', 'Shah Alam')
            ->set('postal_code', '40100')
            ->set('primary_format', 'doubles')
            ->set('preferred_hand', 'right')
            ->set('club_name', 'Smashr Shah Alam')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'amran@example.com')->firstOrFail();

        $this->assertDatabaseHas('player_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Amran Shuttler',
            'doubles_rating' => 3.500,
        ]);
        $this->assertTrue($user->clubs()->where('slug', 'smashr-shah-alam')->exists());
    }

    public function test_rating_updates_only_after_all_players_confirm(): void
    {
        [$winner, $loser] = [$this->player('Winner'), $this->player('Loser')];

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

        $this->assertSame('3.500', $winner->playerProfile->fresh()->singles_rating);

        app(RatingService::class)->confirmForUser($match, $loser->id);

        $this->assertSame('confirmed', $match->fresh()->status);
        $this->assertGreaterThan(3.5, (float) $winner->playerProfile->fresh()->singles_rating);
        $this->assertLessThan(3.5, (float) $loser->playerProfile->fresh()->singles_rating);
        $this->assertSame(2, RatingEvent::where('match_id', $match->id)->count());
    }

    public function test_doubles_rating_is_separate_from_singles_rating(): void
    {
        [$a1, $a2, $b1, $b2] = [
            $this->player('A One'),
            $this->player('A Two'),
            $this->player('B One'),
            $this->player('B Two'),
        ];

        $match = MatchRecord::create([
            'format' => 'doubles',
            'submitted_by' => $a1->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 19], ['a' => 21, 'b' => 18]],
            'winner_side' => 'A',
        ]);

        foreach ([[$a1, 'A', 1], [$a2, 'A', 2], [$b1, 'B', 1], [$b2, 'B', 2]] as [$user, $side, $position]) {
            $match->players()->create([
                'user_id' => $user->id,
                'side' => $side,
                'position' => $position,
                'confirmed_at' => $user->is($a1) ? now() : null,
            ]);
        }

        foreach ([$a2, $b1, $b2] as $user) {
            app(RatingService::class)->confirmForUser($match->fresh(), $user->id);
        }

        $this->assertSame('3.500', $a1->playerProfile->fresh()->singles_rating);
        $this->assertNotSame('3.500', $a1->playerProfile->fresh()->doubles_rating);
    }

    public function test_rankings_hide_players_without_confirmed_matches(): void
    {
        $rated = $this->player('Rated Player');
        $unrated = $this->player('Unrated Player');

        $rated->playerProfile->forceFill(['singles_rating' => 3.900, 'singles_matches' => 1])->save();

        $this->get('/rankings?format=singles')
            ->assertOk()
            ->assertSee('Rated Player')
            ->assertDontSee('Unrated Player');
    }

    public function test_public_and_auth_pages_render(): void
    {
        $user = $this->player('Smoke Player');
        $club = Club::create(['name' => 'Smoke Club', 'slug' => 'smoke-club']);
        $user->clubs()->attach($club);

        $this->get('/')
            ->assertOk()
            ->assertSee('SMASHR - Badminton ratings, draws and live scores - Home')
            ->assertSee('Know your level');
        $this->get(route('players.show', $user->playerProfile))->assertOk()->assertSee('Smoke Player');
        $this->get(route('clubs.show', $club))->assertOk()->assertSee('Smoke Club');
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('SMASHR - Badminton ratings, draws and live scores - Dashboard')
            ->assertSee('Player dashboard');
    }

    public function test_public_match_index_renders_confirmed_matches(): void
    {
        [$winner, $loser] = [$this->player('Index Winner'), $this->player('Index Loser')];

        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $winner->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 14], ['a' => 21, 'b' => 17]],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $winner->id, 'side' => 'A', 'position' => 1, 'confirmed_at' => now()]);
        $match->players()->create(['user_id' => $loser->id, 'side' => 'B', 'position' => 1]);

        app(RatingService::class)->confirmForUser($match, $loser->id);

        $this->get('/matches')
            ->assertOk()
            ->assertSee('Submitted matches')
            ->assertSee('Index Winner')
            ->assertSee('Index Loser')
            ->assertSee('Game 1')
            ->assertSee('21 - 14')
            ->assertSee('Game 2')
            ->assertSee('21 - 17');
    }

    public function test_public_match_index_hides_unsubmitted_match_placeholders(): void
    {
        [$winner, $loser] = [$this->player('Pending Winner'), $this->player('Pending Loser')];

        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $winner->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $winner->id, 'side' => 'A', 'position' => 1]);
        $match->players()->create(['user_id' => $loser->id, 'side' => 'B', 'position' => 1]);

        $this->get('/matches')
            ->assertOk()
            ->assertSee('Submitted matches')
            ->assertDontSee('Pending Winner')
            ->assertDontSee('Pending Loser')
            ->assertDontSee('Match points not submitted yet');
    }

    public function test_public_match_index_renders_doubles_match_points(): void
    {
        [$a1, $a2, $b1, $b2] = [
            $this->player('Doubles Index A One'),
            $this->player('Doubles Index A Two'),
            $this->player('Doubles Index B One'),
            $this->player('Doubles Index B Two'),
        ];

        $match = MatchRecord::create([
            'format' => 'doubles',
            'submitted_by' => $a1->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 19], ['a' => 21, 'b' => 18]],
            'winner_side' => 'A',
        ]);

        foreach ([[$a1, 'A', 1], [$a2, 'A', 2], [$b1, 'B', 1], [$b2, 'B', 2]] as [$user, $side, $position]) {
            $match->players()->create(['user_id' => $user->id, 'side' => $side, 'position' => $position]);
        }

        $this->get('/matches?format=doubles')
            ->assertOk()
            ->assertSee('Doubles Index A One / Doubles Index A Two')
            ->assertSee('Doubles Index B One / Doubles Index B Two')
            ->assertSee('Game 1')
            ->assertSee('21 - 19')
            ->assertSee('Game 2')
            ->assertSee('21 - 18');
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
