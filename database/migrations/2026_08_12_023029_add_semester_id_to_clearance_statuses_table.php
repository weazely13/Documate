<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clearance_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('clearance_statuses', 'semester_id')) {
                // Nullable + nullOnDelete on purpose: if the admin ever deletes
                // a semester, existing history rows should NOT be destroyed,
                // they just lose the link (the old string columns still show
                // what semester/AY they were tagged for).
                $table->foreignId('semester_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('semesters')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('clearance_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('clearance_statuses', 'semester_id')) {
                $table->dropForeign(['semester_id']);
                $table->dropColumn('semester_id');
            }
        });
    }
};