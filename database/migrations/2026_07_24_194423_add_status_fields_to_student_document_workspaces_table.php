<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed'])->default('pending')->after('last_generated_at');
            $table->string('appointment_status')->default('Pending')->after('status');
            $table->date('appointment_date')->nullable()->after('appointment_status');
            $table->string('session_label')->nullable()->after('appointment_date');
            $table->string('supporting_file_path')->nullable()->after('session_label');
            $table->text('ocr_text')->nullable()->after('supporting_file_path');
            $table->timestamp('completed_at')->nullable()->after('ocr_text');
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'appointment_status', 'appointment_date',
                'session_label', 'supporting_file_path', 'ocr_text', 'completed_at',
            ]);
        });
    }
};