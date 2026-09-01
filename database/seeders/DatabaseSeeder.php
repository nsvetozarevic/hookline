<?php

declare(strict_types=1);

namespace Database\Seeders;

use Domain\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'password' => 'password123',
            ],
        );
    }
}
