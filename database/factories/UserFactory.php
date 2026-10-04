<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            // Identity
            'student_number' => fake()->unique()->numerify('2024-#####'),

            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'suffix' => null,

            // Personal
            'sex' => fake()->randomElement(['Male', 'Female']),
            'date_of_birth' => fake()->date(),

            // Contact
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'contact_number' => fake()->phoneNumber(),

            // Academic
            'college' => 'CCS',
            'year_level' => '3',
            'academic_status' => 'Regular',

            // Auth
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // RBAC
            'role_id' => Role::firstOrCreate(['role_name' => 'Student'])->id,

            // Account
            'account_status' => 'active',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}