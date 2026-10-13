<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Signatory accounts (staff who only sign) have no sex, birth date, program, year level, etc.
 * Any of these student-only columns that is NOT NULL with no default blocks creating such an account
 * ("Field 'sex' doesn't have a default value"). This makes exactly those columns nullable, keeping
 * each column's current type. Columns that are already nullable or have a default are left alone.
 * Required identity columns (names, email, password, role, status) are never touched.
 */
return new class extends Migration
{
    private const STUDENT_ONLY = [
        'student_number', 'middle_name', 'suffix', 'sex', 'date_of_birth', 'contact_number',
        'college', 'program_id', 'organization_id', 'year_level', 'section', 'academic_status',
        'profile_picture', 'verification_version',
    ];

    public function up(): void
    {
        foreach (Schema::getColumns('users') as $column) {
            if (! in_array($column['name'], self::STUDENT_ONLY, true)) {
                continue;
            }

            if ($column['nullable'] || $column['default'] !== null) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE `users` MODIFY `%s` %s NULL DEFAULT NULL',
                $column['name'],
                $column['type']
            ));
        }
    }

    public function down(): void
    {
        // Intentionally empty: putting NOT NULL back would break existing signatory accounts.
    }
};