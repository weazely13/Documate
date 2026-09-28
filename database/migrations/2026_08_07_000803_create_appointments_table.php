<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workspace_id');
            $table->foreign('workspace_id')
                ->references('workspace_id')
                ->on('student_document_workspaces')
                ->cascadeOnDelete();

            $table->string('purpose');
            $table->date('appointment_date');
            $table->enum('session', ['morning', 'afternoon']);
            $table->unsignedSmallInteger('queue_number');

            // pending -> approved|rejected -> (on the day) attended|missed
            // rescheduled is a transitional action, not a resting state: it updates date/session and goes back to pending
            $table->enum('status', ['pending', 'approved', 'rejected', 'attended', 'missed'])->default('pending');

            $table->text('rejection_reason')->nullable();
            $table->unsignedTinyInteger('reschedule_count')->default(0);

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['appointment_date', 'session']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};