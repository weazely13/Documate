<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counselor_availabilities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('available_date');
            $t->time('start_time');
            $t->time('end_time');
            $t->timestamps();
            $t->index(['available_date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counselor_availabilities');
    }
};