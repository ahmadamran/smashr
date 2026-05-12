<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

        $this->call([
            MssMelakaTournamentSoftwareSeeder::class,
        ]);
    }
}
