<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_officers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('officer_position_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('term_year')->nullable();
            $table->timestamps();

            // A student can only hold one officer post at a time. Removing the
            // record (or reassigning) frees them up to change organizations again.
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_officers');
    }
};