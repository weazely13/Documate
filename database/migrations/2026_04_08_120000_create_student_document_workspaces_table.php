<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_document_workspaces', function (Blueprint $table) {
            $table->id('workspace_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('version_id');
            $table->json('field_values')->nullable();
            $table->string('generated_pdf_path')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();

            $table->foreign('template_id')
                ->references('template_id')
                ->on('templates')
                ->cascadeOnDelete();

            $table->foreign('version_id')
                ->references('version_id')
                ->on('template_versions')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'template_id', 'version_id'], 'student_document_workspace_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_document_workspaces');
    }
};
