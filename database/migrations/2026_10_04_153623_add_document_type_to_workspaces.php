<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            // Which code-defined document this is (see App\Workflow\Documents). NULL = legacy template transaction.
            $table->string('document_type', 64)->nullable()->index()->after('workspace_id');
        });

        // Workflow documents have no row in `templates`, so these must allow NULL.
        // If your column types differ (not bigint unsigned), adjust here.
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->change();
            $table->unsignedBigInteger('version_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_document_workspaces', function (Blueprint $table) {
            $table->dropIndex(['document_type']);
            $table->dropColumn('document_type');
        });
    }
};