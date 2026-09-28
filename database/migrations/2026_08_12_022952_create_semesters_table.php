<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            // e.g. "2025-2026"
            $table->string('school_year');

            // "First" | "Second" | "Summer" — kept as a string so you can
            // add more terms later without a migration.
            $table->string('semester_label');

            // Only one row in the whole table should ever be true.
            // Enforced in App\Models\Semester::makeCurrent().
            $table->boolean('is_current')->default(false);

            $table->timestamps();

            // A given semester_label can only exist once per school year.
            $table->unique(['school_year', 'semester_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};