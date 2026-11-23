<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The University Doctor signs the Student Advisement Form (with remarks) when a student is sent to the doctor. */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('signatory_roles')->where('key', 'university_doctor')->exists()) {
            DB::table('signatory_roles')->insert([
                'key' => 'university_doctor',
                'label' => 'University Doctor',
                'scope' => 'none',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('signatory_roles')->where('key', 'university_doctor')->delete();
    }
};
