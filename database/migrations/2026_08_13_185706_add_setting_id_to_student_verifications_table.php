<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('student_verifications', 'setting_id')) {
                // Which admin-raised verification round this submission belongs
                // to. Nullable + nullOnDelete: if the admin ever deletes a
                // settings row, submission history stays intact, it just
                // loses the direct link (semester/academic_year columns on
                // this table already record the human-readable context).
                $table->foreignId('setting_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('settings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_verifications', function (Blueprint $table) {
            if (Schema::hasColumn('student_verifications', 'setting_id')) {
                $table->dropForeign(['setting_id']);
                $table->dropColumn('setting_id');
            }
        });
    }
};