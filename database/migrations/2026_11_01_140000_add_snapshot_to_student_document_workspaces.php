<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an online transaction looked like when it was submitted and when it was completed:
 * the form's name, every field's label and answer, and the rendered page (saved as a file).
 * The archive falls back on it when the form's code changes or is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->json('snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });
    }
};
