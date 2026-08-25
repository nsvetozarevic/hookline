<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);
    }
}
