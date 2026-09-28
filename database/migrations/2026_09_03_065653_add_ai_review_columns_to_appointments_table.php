<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // pending        = not yet reviewed / re-review queued
            // clean          = AI found no issues
            // flagged        = AI found one or more issues, needs human eyes
            // review_failed  = the AI call errored out (bad response, API down, etc.)
            $table->string('ai_flag', 20)->default('pending')->after('status');
            $table->json('ai_findings')->nullable()->after('ai_flag');
            $table->timestamp('ai_reviewed_at')->nullable()->after('ai_findings');

            $table->index('ai_flag');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['ai_flag']);
            $table->dropColumn(['ai_flag', 'ai_findings', 'ai_reviewed_at']);
        });
    }
};