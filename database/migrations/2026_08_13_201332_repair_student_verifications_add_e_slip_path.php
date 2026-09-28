<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('student_verifications', 'e_slip_path')) {
                $table->string('e_slip_path')->nullable()->after('student_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('student_verifications', 'e_slip_path')) {
                $table->dropColumn('e_slip_path');
            }
        });
    }
};