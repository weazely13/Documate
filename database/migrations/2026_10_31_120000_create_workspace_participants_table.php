<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Students named on another student's transaction (e.g. the interviewees on a College Model
 * interview rating). They are notified and can open a read-only copy of the document.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40)->default('participant');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('workspace_id')->on('student_document_workspaces')->cascadeOnDelete();
            $table->unique(['workspace_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_participants');
    }
};
