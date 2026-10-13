<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per required signature on one transaction. step_order / role / label are
        // copied from the template when signing starts, so later edits to a template's chain
        // never change transactions already in flight.
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('student_document_workspaces', 'workspace_id')->cascadeOnDelete();
            $table->foreignId('template_signatory_step_id')->nullable()->constrained('template_signatory_steps')->nullOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('signatory_role_id')->constrained('signatory_roles')->restrictOnDelete();
            $table->string('label');
            $table->string('status', 16)->default('waiting')->index();
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();               // required when rejected
            $table->string('auth_method', 32)->nullable();    // 'account' now; 'email_code' / 'otp' later
            $table->string('signature_path')->nullable();     // snapshot of the signature used
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'step_order']);
            $table->index(['status', 'signatory_role_id']);   // signatory inbox query
        });

        // Append-only. The model refuses updates and deletes.
        Schema::create('transaction_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('student_document_workspaces', 'workspace_id')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 64)->nullable();     // snapshot at the time of the action
            $table->string('action', 64)->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->unsignedBigInteger('approval_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_audit_logs');
        Schema::dropIfExists('transaction_approvals');
    }
};