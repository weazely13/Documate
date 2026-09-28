<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_availabilities', function (Blueprint $table) {
            $table->unsignedSmallInteger('now_serving_morning')->nullable()->after('afternoon_slots');
            $table->unsignedSmallInteger('now_serving_afternoon')->nullable()->after('now_serving_morning');
        });
    }

    public function down(): void
    {
        Schema::table('office_availabilities', function (Blueprint $table) {
            $table->dropColumn(['now_serving_morning', 'now_serving_afternoon']);
        });
    }
};