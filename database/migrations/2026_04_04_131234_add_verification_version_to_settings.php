<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'verification_version')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->integer('verification_version')->default(1);
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('settings', 'verification_version')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('verification_version');
        });
    }
};
