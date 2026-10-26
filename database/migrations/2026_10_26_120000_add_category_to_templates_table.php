<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paper templates get the same categories as paperless forms (App\Enums\DocumentCategory),
     * so the student's New transaction page can group them together. Null = not categorized yet.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (! Schema::hasColumn('templates', 'category')) {
                $table->string('category', 32)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
