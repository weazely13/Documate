<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->boolean('processing')->default(false)->after('supporting_uploaded_at');
            $table->enum('analysis_status', ['pending', 'approved', 'rejected'])->default('pending')->after('processing');
            $table->text('analysis_summary')->nullable()->after('analysis_status');
            $table->json('analysis_data')->nullable()->after('analysis_summary'); // fields_complete, has_signature, missing_fields, etc.
            $table->text('rejection_reason')->nullable()->after('analysis_data');
            $table->timestamp('analyzed_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropColumn(['processing', 'analysis_status', 'analysis_summary', 'analysis_data', 'rejection_reason', 'analyzed_at']);
        });
    }
};