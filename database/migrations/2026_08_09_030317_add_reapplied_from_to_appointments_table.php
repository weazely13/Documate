<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('reapplied_from_id')
                ->nullable()
                ->after('workspace_id')
                ->constrained('appointments', 'appointment_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['reapplied_from_id']);
            $table->dropColumn('reapplied_from_id');
        });
    }
};