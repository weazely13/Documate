<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports a signatory started on the Issue page and saved for later. Kept apart from
 * student_document_workspaces so a half-written report never shows up in the student's own documents.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issuer_id')->constrained('users')->cascadeOnDelete();
            $table->string('document_type', 80);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('field_values')->nullable();
            $table->timestamps();
            $table->index(['issuer_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_drafts');
    }
};
