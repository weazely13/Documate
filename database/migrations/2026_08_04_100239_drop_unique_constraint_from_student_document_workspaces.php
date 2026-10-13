<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('student_document_workspaces_user_id_index', 'user_id');
        $this->addIndexIfMissing('student_document_workspaces_template_id_index', 'template_id');
        $this->addIndexIfMissing('student_document_workspaces_version_id_index', 'version_id');
        $this->dropIndexIfExists('student_document_workspace_unique');
    }

    public function down(): void
    {
        if (!$this->indexExists('student_document_workspace_unique')) {
            Schema::table('student_document_workspaces', function ($table) {
                $table->unique(['user_id', 'template_id', 'version_id'], 'student_document_workspace_unique');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return Schema::hasIndex('student_document_workspaces', $indexName);
    }

    private function addIndexIfMissing(string $indexName, string $column): void
    {
        if (!$this->indexExists($indexName)) {
            Schema::table('student_document_workspaces', function ($table) use ($indexName, $column) {
                $table->index($column, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            Schema::table('student_document_workspaces', function ($table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};