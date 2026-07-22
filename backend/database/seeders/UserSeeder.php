<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@sinodtech.test',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Employee User',
            'email' => 'employee@sinodtech.test',
            'password' => 'password',
            'role' => UserRole::Employee,
        ]);

        $apiConsumer = User::factory()->create([
            'name' => 'E-Commerce API Consumer',
            'email' => 'api-consumer@sinodtech.test',
            'password' => 'password',
            'role' => UserRole::ApiConsumer,
        ]);

        $token = $apiConsumer->createToken('ecommerce-api', ['products:read'])->plainTextToken;

        $this->command?->info("API consumer token (products:read ability): {$token}");
    }
}
