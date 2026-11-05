<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The Year-level Adviser signs the General Students' Clearance (F-SDM-020 Rev. 2); one per year level. */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('signatory_roles')->where('key', 'year_level_adviser')->exists()) {
            DB::table('signatory_roles')->insert([
                'key' => 'year_level_adviser',
                'label' => 'Year-level Adviser',
                'scope' => 'year_level',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('signatory_roles')->where('key', 'year_level_adviser')->delete();
    }
};
