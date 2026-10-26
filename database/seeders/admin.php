<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class admin extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ],
        );

        if (! $admin->isAdmin()) {
            $admin->forceFill(['role' => 'admin'])->save();
        }
    }
}
