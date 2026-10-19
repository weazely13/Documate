<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // 1-4 marks a year level organization (Freshmen = 1 ... Senior = 4). NULL = an ordinary organization.
            $table->unsignedTinyInteger('year_level')->nullable()->after('is_academic');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }
};