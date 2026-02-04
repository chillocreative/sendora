<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@blaster.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Rahim1977@'), // Using your preferred password
                'email_verified_at' => now(),
            ]
        );
    }
}
