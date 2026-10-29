<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feedback and issue reports sent by users to the office
        Schema::create('feedback_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16);                       // feedback | issue
            $table->string('category', 32);
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5, feedback only
            $table->string('severity', 16)->nullable();       // low | medium | high, issues only
            $table->string('subject', 150);
            $table->text('message');
            $table->string('page_url', 500)->nullable();
            $table->string('attachment_path')->nullable();    // private disk
            $table->string('user_agent', 500)->nullable();
            $table->string('status', 16)->default('new')->index();
            $table->text('admin_reply')->nullable();          // shown to the user
            $table->text('internal_note')->nullable();        // admins only
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_reports');
    }
};
