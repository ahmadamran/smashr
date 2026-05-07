<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Clubs\Models\Club;
use Modules\Players\Models\PlayerProfile;
use Modules\Ratings\Models\RatingAlgorithm;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // User::factory(10)->create();

        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ],
        );

        $club = Club::query()->firstOrCreate(
            ['slug' => 'smashr-kl'],
            [
                'name' => 'Smashr KL',
                'country' => 'Malaysia',
                'state' => 'Kuala Lumpur',
                'city' => 'Kuala Lumpur',
                'description' => 'A flagship Smashr badminton community.',
            ],
        );

        PlayerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
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

        $user->clubs()->syncWithoutDetaching([$club->id]);

        $superadminRole = Role::findOrCreate('superadmin', 'web');

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@smashr.test'],
            [
                'name' => 'Smashr Superadmin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ],
        );

        $admin->assignRole($superadminRole);

        PlayerProfile::query()->firstOrCreate(
            ['user_id' => $admin->id],
            [
                'display_name' => 'Smashr Superadmin',
                'slug' => 'smashr-superadmin',
                'country' => 'Malaysia',
                'state' => 'Kuala Lumpur',
                'city' => 'Kuala Lumpur',
                'preferred_hand' => 'right',
                'primary_format' => 'doubles',
            ],
        );

        RatingAlgorithm::query()->firstOrCreate(
            ['version' => 'v1'],
            [
                'created_by' => $admin->id,
                'name' => 'Smashr Rating v1',
                'status' => 'active',
                'settings' => RatingAlgorithm::DEFAULT_SETTINGS,
                'activated_at' => now(),
            ],
        );

        $this->call(DemoCompetitionSeeder::class);
    }
}
