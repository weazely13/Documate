<?php

namespace Database\Seeders;

use App\Models\SignatoryRole;
use Illuminate\Database\Seeder;

class SignatoryRoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['guidance_counselor', 'Guidance Counselor'],
            ['vpsd', 'Vice President for Student Development'],
            ['vpsd_secretary', 'VPSD Secretary'],
        ] as [$key, $label]) {
            SignatoryRole::updateOrCreate(['key' => $key], ['label' => $label, 'is_active' => true]);
        }
        $roles = [
            // global
            ['vpsd', 'Vice President for Student Development', 'none'],
            ['guidance_counselor', 'Guidance Counselor', 'none'],
            ['vpas', 'VP for Academic Affairs', 'none'],
            ['director_soa', 'Director, Student Organizations and Activities', 'none'],
            ['chief_librarian', 'College Chief Librarian', 'none'],
            ['university_accountant', 'University Accountant', 'none'],
            ['ssc_president', 'SSC President', 'none'],
            ['ssc_secretary', 'SSC Secretary', 'none'],
            ['ssc_treasurer', 'SSC Treasurer', 'none'],
            ['faculty', 'Faculty', 'none'],
            ['security_guard', 'Security Guard', 'none'],
            ['personnel', 'Personnel', 'none'],
            ['discipline_chairman', 'Discipline Committee Chairman', 'none'],
            // scoped
            ['org_adviser', 'Organization Adviser', 'organization'],
            ['org_treasurer', 'Organization Treasurer', 'organization'],
            ['program_chair', 'Academic Chair / Program Coordinator', 'program'],
            ['college_dean', 'College Dean', 'college'],
        
        ];

        foreach ($roles as [$key, $label, $scope]) {
            \App\Models\SignatoryRole::firstOrCreate(['key' => $key], ['label' => $label, 'scope' => $scope, 'is_active' => true]);
        }
    }
}
