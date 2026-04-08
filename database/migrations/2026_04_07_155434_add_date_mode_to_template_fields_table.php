<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->string('date_mode', 10)->default('current')->after('data_type');
        });
    }

    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn('date_mode');
        });
    }
};