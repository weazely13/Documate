<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // ✅ Role IDs
        $studentRoleId = Role::where('role_name', 'Student')->value('id');
        $officerRoleId = Role::where('role_name', 'Officer')->value('id');
        $adminRoleId   = Role::where('role_name', 'Admin')->value('id');

        $college = College::query()->firstOrCreate(
            ['code' => 'CCS'],
            ['name' => 'College of Computer Studies'],
        );

        $program = Program::query()->firstOrCreate(
            ['name' => 'BSIT'],
            ['college_id' => $college->id, 'is_active' => true],
        );

        $organization = Organization::query()->firstOrCreate(
            ['name' => 'DIGITS'],
            ['description' => 'Sample student organization', 'is_active' => true],
        );
        $organization->programs()->syncWithoutDetaching([$program->id]);

        $password = Hash::make('#Password123');

        $studentNumber = 2100200;

        // =========================
        // 🎓 STUDENTS (10)
        // =========================
        for ($i = 0; $i < 10; $i++) {
            User::firstOrCreate(
                ['student_number' => $studentNumber++],
                [
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->optional()->firstName(),
                'last_name' => fake()->lastName(),
                'suffix' => null,

                'sex' => fake()->randomElement(['Male', 'Female']),
                'date_of_birth' => fake()->date('Y-m-d', '2005-01-01'),

                'email' => fake()->unique()->safeEmail(),
                'contact_number' => fake()->phoneNumber(),

                'college' => 'CCS',
                'program_id' => $program->id,
                'organization_id' => $organization->id,
                'year_level' => fake()->randomElement(['1', '2', '3', '4']),
                'academic_status' => 'Regular',

                'password' => $password,
                'role_id' => $studentRoleId,

                'profile_picture' => null,

                // 🔥 Must verify first
                'account_status' => 'pending_verification',
                ],
            );
        }

        // =========================
        // 🏅 OFFICERS (5)
        // =========================
        for ($i = 0; $i < 5; $i++) {
            User::firstOrCreate(
                ['student_number' => $studentNumber++],
                [
                'first_name' => fake()->firstName(),
                'middle_name' => fake()->optional()->firstName(),
                'last_name' => fake()->lastName(),
                'suffix' => null,

                'sex' => fake()->randomElement(['Male', 'Female']),
                'date_of_birth' => fake()->date('Y-m-d', '2005-01-01'),

                'email' => fake()->unique()->safeEmail(),
                'contact_number' => fake()->phoneNumber(),

                'college' => 'CCS',
                'program_id' => $program->id,
                'organization_id' => $organization->id,
                'year_level' => fake()->randomElement(['2', '3', '4']),
                'academic_status' => 'Regular',

                'password' => $password,
                'role_id' => $officerRoleId,

                'profile_picture' => null,
                'account_status' => 'pending_verification',
                ],
            );
        }

        // =========================
        // 👑 ADMIN (1)
        // =========================
        User::firstOrCreate(
            ['student_number' => 'ADMIN-001'],
            [
            'first_name' => 'System',
            'middle_name' => null,
            'last_name' => 'Administrator',
            'suffix' => null,

            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',

            'email' => 'admin@documate.test',
            'contact_number' => '09123456789',

            'college' => 'ADMIN',
            'program_id' => $program->id,
            'organization_id' => null,
            'year_level' => 'N/A',
            'academic_status' => 'Staff',

            'password' => $password,
            'role_id' => $adminRoleId,

            'profile_picture' => null,

            // Admin bypass
            'account_status' => 'active',
            ],
        );
    }
}