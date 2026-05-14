<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;
use Modules\Clubs\Models\Club;
use Modules\Matches\Models\MatchRecord;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Modules\Ratings\Models\RatingEvent;
use Modules\Ratings\Services\RatingRecalculationService;
use Modules\Ratings\Services\RatingService;
use Modules\Tournaments\Models\Tournament;
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

    public function test_mixed_rating_is_separate_from_singles_and_doubles_rating(): void
    {
        [$a1, $a2, $b1, $b2] = [
            $this->player('Mixed A One', 'male'),
            $this->player('Mixed A Two', 'female'),
            $this->player('Mixed B One', 'male'),
            $this->player('Mixed B Two', 'female'),
        ];

        $match = MatchRecord::create([
            'format' => 'mixed',
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

        $profile = $a1->playerProfile->fresh();

        $this->assertSame('3.500', $profile->singles_rating);
        $this->assertSame('3.500', $profile->doubles_rating);
        $this->assertNotSame('3.500', $profile->mixed_rating);
        $this->assertSame(1, $profile->mixed_matches);

        $a1->playerProfile->forceFill(['mixed_rating' => 4.900, 'mixed_matches' => 9])->save();
        app(RatingRecalculationService::class)->apply(RatingAlgorithm::active());

        $rebuilt = $a1->playerProfile->fresh();
        $this->assertNotSame('4.900', $rebuilt->mixed_rating);
        $this->assertSame(1, $rebuilt->mixed_matches);
    }

    public function test_mixed_match_validation_requires_one_male_and_one_female_per_side(): void
    {
        [$a1, $a2, $b1, $b2] = [
            $this->player('Invalid Mixed A One', 'male'),
            $this->player('Invalid Mixed A Two', 'male'),
            $this->player('Invalid Mixed B One', 'male'),
            $this->player('Invalid Mixed B Two', 'female'),
        ];

        $match = MatchRecord::create([
            'format' => 'mixed',
            'submitted_by' => $a1->id,
            'status' => 'pending_confirmation',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 19]],
            'winner_side' => 'A',
        ]);

        foreach ([[$a1, 'A', 1], [$a2, 'A', 2], [$b1, 'B', 1], [$b2, 'B', 2]] as [$user, $side, $position]) {
            $match->players()->create(['user_id' => $user->id, 'side' => $side, 'position' => $position]);
        }

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(RatingService::class)->confirmAsAdmin($match);
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

    public function test_rankings_can_filter_by_gender(): void
    {
        $male = $this->player('Men Ranking Player');
        $female = $this->player('Women Ranking Player');

        $male->playerProfile->forceFill([
            'gender' => 'male',
            'singles_rating' => 3.900,
            'singles_matches' => 1,
        ])->save();
        $female->playerProfile->forceFill([
            'gender' => 'female',
            'singles_rating' => 3.950,
            'singles_matches' => 1,
        ])->save();

        $this->get('/rankings?format=singles&gender=male')
            ->assertOk()
            ->assertSee('Men&#039;s singles leaderboard', false)
            ->assertSee('Men Ranking Player')
            ->assertDontSee('Women Ranking Player');

        $this->get('/rankings?format=singles&gender=female')
            ->assertOk()
            ->assertSee('Women&#039;s singles leaderboard', false)
            ->assertSee('Women Ranking Player')
            ->assertDontSee('Men Ranking Player');
    }

    public function test_rankings_support_mixed_format_and_age_filters(): void
    {
        Carbon::setTestNow('2026-05-14 10:00:00');

        $under12 = $this->player('Under Twelve Mixed Player');
        $under15 = $this->player('Under Fifteen Mixed Player');
        $under18 = $this->player('Under Eighteen Mixed Player');
        $adult = $this->player('Adult Mixed Player');
        $unknownAge = $this->player('Unknown Age Mixed Player');

        $under12->playerProfile->forceFill(['birthdate' => '2014-06-01', 'mixed_rating' => 3.900, 'mixed_matches' => 1])->save();
        $under15->playerProfile->forceFill(['birthdate' => '2011-06-01', 'mixed_rating' => 3.800, 'mixed_matches' => 1])->save();
        $under18->playerProfile->forceFill(['birthdate' => '2008-06-01', 'mixed_rating' => 3.700, 'mixed_matches' => 1])->save();
        $adult->playerProfile->forceFill(['birthdate' => '1990-01-01', 'mixed_rating' => 3.600, 'mixed_matches' => 1])->save();
        $unknownAge->playerProfile->forceFill(['mixed_rating' => 3.500, 'mixed_matches' => 1])->save();

        $this->get('/rankings?format=mixed')
            ->assertOk()
            ->assertSee('Overall mixed leaderboard')
            ->assertSee('Unknown Age Mixed Player');

        $this->get('/rankings?format=mixed&age_group=u12')
            ->assertOk()
            ->assertSee('Under Twelve Mixed Player')
            ->assertDontSee('Under Fifteen Mixed Player')
            ->assertDontSee('Unknown Age Mixed Player');

        $this->get('/rankings?format=mixed&age_group=u15')
            ->assertOk()
            ->assertSee('Under Twelve Mixed Player')
            ->assertSee('Under Fifteen Mixed Player')
            ->assertDontSee('Under Eighteen Mixed Player');

        $this->get('/rankings?format=mixed&age_group=u18')
            ->assertOk()
            ->assertSee('Under Eighteen Mixed Player')
            ->assertDontSee('Adult Mixed Player')
            ->assertDontSee('Unknown Age Mixed Player');

        $this->get('/rankings?format=mixed&age_group=adult')
            ->assertOk()
            ->assertSee('Adult Mixed Player')
            ->assertDontSee('Under Eighteen Mixed Player')
            ->assertDontSee('Unknown Age Mixed Player');

        Carbon::setTestNow();
    }

    public function test_public_submit_result_accepts_mixed_matches(): void
    {
        [$a1, $a2, $b1, $b2] = [
            $this->player('Public Mixed A One', 'male'),
            $this->player('Public Mixed A Two', 'female'),
            $this->player('Public Mixed B One', 'male'),
            $this->player('Public Mixed B Two', 'female'),
        ];

        $this->actingAs($a1);

        Volt::test('matches.create')
            ->set('format', 'mixed')
            ->set('side_a_1', $a1->email)
            ->set('side_a_2', $a2->email)
            ->set('side_b_1', $b1->email)
            ->set('side_b_2', $b2->email)
            ->set('winner_side', 'A')
            ->set('played_at', now()->toDateString())
            ->call('submit');

        $match = MatchRecord::where('format', 'mixed')->latest('id')->firstOrFail();

        $this->assertSame(4, $match->players()->count());
        $this->assertDatabaseHas('match_players', ['match_id' => $match->id, 'user_id' => $a2->id, 'side' => 'A']);
        $this->assertDatabaseHas('match_players', ['match_id' => $match->id, 'user_id' => $b2->id, 'side' => 'B']);
    }

    public function test_rankings_can_search_by_player_name(): void
    {
        $alpha = $this->player('Searchable Alpha Player');
        $beta = $this->player('Different Beta Player');

        $alpha->playerProfile->forceFill([
            'singles_rating' => 3.900,
            'singles_matches' => 1,
        ])->save();
        $beta->playerProfile->forceFill([
            'singles_rating' => 3.950,
            'singles_matches' => 1,
        ])->save();

        $this->get('/rankings?format=singles&search=Alpha')
            ->assertOk()
            ->assertSee('Searchable Alpha Player')
            ->assertDontSee('Different Beta Player');
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
        $this->get(route('players.show', $user->playerProfile))
            ->assertOk()
            ->assertSee('Smoke Player')
            ->assertSee('Unrated')
            ->assertSee('0 confirmed matches');
        $this->get(route('clubs.show', $club))->assertOk()->assertSee('Smoke Club');
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('SMASHR - Badminton ratings, draws and live scores - Dashboard')
            ->assertSee('Player dashboard');
    }

    public function test_public_club_index_can_search_by_name_and_location(): void
    {
        Club::create([
            'name' => 'Kuching Smash Club',
            'slug' => 'kuching-smash-club',
            'country' => 'Malaysia',
            'state' => 'Sarawak',
            'city' => 'Kuching',
        ]);
        Club::create([
            'name' => 'Hidden City Club',
            'slug' => 'hidden-city-club',
            'country' => 'Malaysia',
            'state' => 'Selangor',
            'city' => 'Shah Alam',
        ]);

        $this->get('/clubs?search=Kuching')
            ->assertOk()
            ->assertSee('Kuching Smash Club')
            ->assertDontSee('Hidden City Club');

        $this->get('/clubs?search=Sarawak')
            ->assertOk()
            ->assertSee('Kuching Smash Club')
            ->assertDontSee('Hidden City Club');
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

    public function test_public_match_index_searches_player_club_and_tournament_names(): void
    {
        [$winner, $loser, $hidden] = [
            $this->player('Search Winner'),
            $this->player('Search Loser'),
            $this->player('Hidden Match Player'),
        ];
        $club = Club::create(['name' => 'Search Club', 'slug' => 'search-club']);
        $tournament = Tournament::create([
            'name' => 'Search Tournament',
            'slug' => 'search-tournament',
            'status' => 'published',
        ]);

        $match = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $winner->id,
            'club_id' => $club->id,
            'tournament_id' => $tournament->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 14]],
            'winner_side' => 'A',
        ]);
        $match->players()->create(['user_id' => $winner->id, 'side' => 'A', 'position' => 1]);
        $match->players()->create(['user_id' => $loser->id, 'side' => 'B', 'position' => 1]);

        $hiddenMatch = MatchRecord::create([
            'format' => 'singles',
            'submitted_by' => $hidden->id,
            'status' => 'confirmed',
            'played_at' => now()->toDateString(),
            'score' => [['a' => 21, 'b' => 10]],
            'winner_side' => 'A',
        ]);
        $hiddenMatch->players()->create(['user_id' => $hidden->id, 'side' => 'A', 'position' => 1]);
        $hiddenMatch->players()->create(['user_id' => $this->player('Other Hidden Player')->id, 'side' => 'B', 'position' => 1]);

        $this->get('/matches?search=Search+Winner')
            ->assertOk()
            ->assertSee('Search Winner')
            ->assertDontSee('Hidden Match Player');

        $this->get('/matches?search=Search+Club')
            ->assertOk()
            ->assertSee('Search Winner')
            ->assertSee('Search Club')
            ->assertDontSee('Hidden Match Player');

        $this->get('/matches?search=Search+Tournament')
            ->assertOk()
            ->assertSee('Search Winner')
            ->assertSee('Search Tournament')
            ->assertDontSee('Hidden Match Player');
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
            ->assertSee('Doubles Index A One')
            ->assertSee('Doubles Index A Two')
            ->assertSee('Doubles Index B One')
            ->assertSee('Doubles Index B Two')
            ->assertSee('Game 1')
            ->assertSee('21 - 19')
            ->assertSee('Game 2')
            ->assertSee('21 - 18');
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
}
