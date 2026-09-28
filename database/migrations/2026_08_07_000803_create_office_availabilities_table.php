<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_availabilities', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('morning_open')->default(true);
            $table->boolean('afternoon_open')->default(true);
            $table->unsignedSmallInteger('morning_slots')->default(25);
            $table->unsignedSmallInteger('afternoon_slots')->default(25);
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_availabilities');
    }
};