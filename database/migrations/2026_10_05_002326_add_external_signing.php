<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_approvals', function (Blueprint $table) {
            // External steps have no signatory role. If your column is not bigint unsigned, adjust.
            $table->unsignedBigInteger('signatory_role_id')->nullable()->change();

            $table->string('signer_type', 16)->default('internal')->after('label'); // internal | external
            $table->string('external_name')->nullable();
            $table->string('external_email')->nullable();
            $table->json('response')->nullable(); // what the signer answered (e.g. the rating); covered by the seal
        });

        Schema::create('signing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('transaction_approvals')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('student_document_workspaces', 'workspace_id')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();       // sha256 of the emailed token; the raw token is never stored
            $table->string('email');                        // the invited address, lowercase
            $table->string('status', 16)->default('pending'); // pending | completed | revoked
            $table->timestamp('expires_at');
            $table->char('code_hash', 64)->nullable();      // HMAC of the current one-time code
            $table->timestamp('code_expires_at')->nullable();
            $table->unsignedTinyInteger('code_attempts')->default(0);
            $table->timestamp('code_sent_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['approval_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signing_requests');

        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->dropColumn(['signer_type', 'external_name', 'external_email', 'response']);
        });
    }
};