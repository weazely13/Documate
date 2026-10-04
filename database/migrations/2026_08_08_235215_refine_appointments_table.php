<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->nullable()->change();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('workspace_id')
                ->references('workspace_id')->on('student_document_workspaces')
                ->nullOnDelete();
            $table->text('admin_notes')->nullable()->after('rejection_reason');
            $table->text('reschedule_reason')->nullable()->after('admin_notes');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY status ENUM('pending','approved','rejected','attended','missed','retracted') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['admin_notes', 'reschedule_reason']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY status ENUM('pending','approved','rejected','attended','missed') DEFAULT 'pending'");
        }
    }
};