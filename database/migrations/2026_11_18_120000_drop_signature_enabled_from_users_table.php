<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the "don't show my signature" option. Every signer's saved e-signature is now always printed on the
 * documents they approve or issue, so the per-user switch (users.signature_enabled) is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'signature_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('signature_enabled');
            });
        }
    }

    /** Puts the column back with everyone's signature shown (the old default); the option itself stays removed in the app. */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'signature_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('signature_enabled')->default(true);
            });
        }
    }
};
