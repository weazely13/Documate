<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->foreignId('signer_user_id')
                ->nullable()
                ->after('signatory_role_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signer_user_id');
        });
    }
};