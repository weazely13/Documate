<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('template_fields', 'name')) {
                $table->string('name')->nullable()->after('group_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('template_fields', function (Blueprint $table) {
            if (Schema::hasColumn('template_fields', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
