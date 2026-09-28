<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        $result = DB::select(
            "SELECT COUNT(1) as count FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE()
               AND table_name = 'student_document_workspaces'
               AND index_name = ?",
            [$indexName]
        );

        return (int) $result[0]->count > 0;
    }

    private function addIndexIfMissing(string $indexName, string $column): void
    {
        if (!$this->indexExists($indexName)) {
            DB::statement("ALTER TABLE student_document_workspaces ADD INDEX {$indexName} ({$column})");
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        if ($this->indexExists($indexName)) {
            DB::statement("ALTER TABLE student_document_workspaces DROP INDEX {$indexName}");
        }
    }
};