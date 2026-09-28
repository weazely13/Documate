<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            // 'none' | 'uppercase' | 'sentence' | 'smallcaps'
            $table->string('text_case', 20)->default('none')->after('letter_spacing');
        });
    }

    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            $table->dropColumn('text_case');
        });
    }
};