<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->string('group_name')->nullable()->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};