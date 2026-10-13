<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->unique(); // public, unguessable, printed as QR
            $table->char('content_hash', 64)->nullable();                   // fingerprint of the final answers
            $table->char('pdf_sha256', 64)->nullable();                     // hash of the issued PDF file
            $table->timestamp('sealed_at')->nullable();
        });

        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->string('signer_name')->nullable();       // frozen at signing time
            $table->char('signature_hash', 64)->nullable();  // sha256 of the signature image used
            $table->char('content_hash', 64)->nullable();    // fingerprint of the document the signer saw
            $table->char('seal', 64)->nullable();            // HMAC over everything above + who/when/how
        });

        Schema::table('transaction_audit_logs', function (Blueprint $table) {
            $table->char('prev_hash', 64)->nullable();
            $table->char('hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_audit_logs', fn (Blueprint $t) => $t->dropColumn(['prev_hash', 'hash']));
        Schema::table('transaction_approvals', fn (Blueprint $t) => $t->dropColumn(['signer_name', 'signature_hash', 'content_hash', 'seal']));

        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropUnique(['verification_token']);
            $table->dropColumn(['verification_token', 'content_hash', 'pdf_sha256', 'sealed_at']);
        });
    }
};