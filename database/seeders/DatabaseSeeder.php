<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
        ]);

        User::factory()->withPersonalTeam()->create([
            'name' => 'Admin User',
            'email' => 'admin@blaster.com',
            'password' => bcrypt('password123'),
        ]);
    }
}
