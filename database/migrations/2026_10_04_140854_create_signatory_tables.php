<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatory_roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();      // e.g. guidance_counselor
            $table->string('label');                   // e.g. Guidance Counselor
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Who currently holds a role. Date range lets the office swap holders without touching templates.
        Schema::create('signatory_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('signatory_role_id')->constrained('signatory_roles')->cascadeOnDelete();
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['signatory_role_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
        });

        // The approval chain of a template, in order.
        Schema::create('template_signatory_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates', 'template_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('step_order');
            $table->foreignId('signatory_role_id')->constrained('signatory_roles')->restrictOnDelete();
            $table->string('label');                   // e.g. Recommending Approval
            $table->timestamps();

            $table->unique(['template_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_signatory_steps');
        Schema::dropIfExists('signatory_assignments');
        Schema::dropIfExists('signatory_roles');
    }
};