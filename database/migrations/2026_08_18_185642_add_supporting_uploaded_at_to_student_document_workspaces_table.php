<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->timestamp('supporting_uploaded_at')->nullable()->after('supporting_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropColumn('supporting_uploaded_at');
        });
    }
};