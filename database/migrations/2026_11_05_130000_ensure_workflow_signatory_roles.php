<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Every signing role the online forms use (the three clearances included) must exist, or sending one for signing
 * fails with "Signatory role … does not exist". This creates any that are missing, so a deploy needs no seeding.
 * Roles that already exist are left exactly as they are (label, scope, and who holds them).
 */
return new class extends Migration
{
    private const ROLES = [
        ['guidance_counselor', 'Guidance Counselor', 'none'],
        ['vpsd', 'Vice President for Student Development', 'none'],
        ['vpsd_secretary', 'VPSD Secretary', 'none'],
        ['vpas', 'VP for Academic Affairs', 'none'],
        ['director_soa', 'Director, Student Organizations and Activities', 'none'],
        ['chief_librarian', 'College Chief Librarian', 'none'],
        ['university_accountant', 'University Accountant', 'none'],
        ['ssc_president', 'SSC President', 'none'],
        ['ssc_secretary', 'SSC Secretary', 'none'],
        ['ssc_treasurer', 'SSC Treasurer', 'none'],
        ['ssc_adviser', 'SSC Adviser', 'none'],
        ['faculty', 'Faculty', 'none'],
        ['security_guard', 'Security Guard', 'none'],
        ['personnel', 'Personnel', 'none'],
        ['discipline_chairman', 'Discipline Committee Chairman', 'none'],
        ['university_doctor', 'University Doctor', 'none'],
        ['org_adviser', 'Organization Adviser', 'organization'],
        ['org_treasurer', 'Organization Treasurer', 'organization'],
        ['program_chair', 'Academic Chair / Program Coordinator', 'program'],
        ['college_dean', 'College Dean', 'college'],
        ['homeroom_adviser', 'Homeroom Adviser', 'program'],
        ['year_level_adviser', 'Year-level Adviser', 'year_level'],
    ];

    public function up(): void
    {
        $existing = DB::table('signatory_roles')->pluck('key')->all();

        foreach (self::ROLES as [$key, $label, $scope]) {
            if (in_array($key, $existing, true)) {
                continue;
            }
            DB::table('signatory_roles')->insert([
                'key' => $key,
                'label' => $label,
                'scope' => $scope,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Nothing: roles may already be assigned to people, so they are not removed.
    }
};
