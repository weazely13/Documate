<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->unsignedBigInteger('superseded_by')->nullable();      // workspace_id of the corrected draft
            $table->unsignedBigInteger('replaces_workspace_id')->nullable(); // set on the corrected draft
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropColumn(['voided_at', 'voided_by', 'void_reason', 'superseded_by', 'replaces_workspace_id']);
        });
    }
};