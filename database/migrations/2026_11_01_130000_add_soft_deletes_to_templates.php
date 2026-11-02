<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a template only hides it. A hard delete cascaded to every paper transaction made from it
 * (student_document_workspaces.template_id is ON DELETE CASCADE), wiping Transaction Records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
