<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Organization',
                'description' => 'Default workspace for development and testing',
                'is_active' => true,
            ],
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'organization_id' => $organization->id,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $organization->members()->syncWithoutDetaching([
            $user->id => [
                'role' => 'admin',
                'joined_at' => now(),
            ],
        ]);
    }
}
