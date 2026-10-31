<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The AI pre-check on workflow transactions, same shape as the one on appointments. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->string('ai_flag', 20)->nullable()->index(); // pending | clean | flagged | review_failed
            $table->json('ai_findings')->nullable();
            $table->timestamp('ai_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropIndex(['ai_flag']);
            $table->dropColumn(['ai_flag', 'ai_findings', 'ai_reviewed_at']);
        });
    }
};
