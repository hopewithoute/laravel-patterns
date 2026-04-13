<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default organization
        $organization = Organization::create([
            'name' => 'Default Organization',
            'slug' => 'default',
            'description' => 'Default workspace for development and testing',
            'is_active' => true,
        ]);

        // Create default user
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        // Add user to organization as admin
        $organization->addMember($user, 'admin');
    }
}
