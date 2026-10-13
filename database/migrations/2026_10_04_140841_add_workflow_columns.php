<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Templates opt in, so every existing template keeps the legacy behaviour.
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('uses_workflow')->default(false)->after('status');
            $table->boolean('requires_appointment')->default(false)->after('uses_workflow');
        });

        // workflow_status is separate from the legacy `status` column on purpose:
        // NULL = legacy transaction, non-NULL = digital approval workflow.
        // (completed_at already exists on this table and is reused.)
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->string('transaction_code', 32)->nullable()->unique()->after('workspace_id');
            $table->string('workflow_status', 32)->nullable()->index()->after('status');
            $table->text('workflow_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('issued_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable();
        });

        Schema::create('transaction_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_sequences');

        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('signature_path'));

        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropUnique(['transaction_code']);
            $table->dropIndex(['workflow_status']);
            $table->dropColumn(['transaction_code', 'workflow_status', 'workflow_note', 'submitted_at', 'issued_at']);
        });

        Schema::table('templates', fn (Blueprint $t) => $t->dropColumn(['uses_workflow', 'requires_appointment']));
    }
};