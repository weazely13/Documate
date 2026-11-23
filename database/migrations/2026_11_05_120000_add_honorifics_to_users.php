<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Staff accounts: an honorific in front of the name (Dr., Prof. ...) and post-nominals after it (PhD, LPT ...), printed with their signature. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'honorific')) {
                $table->string('honorific', 40)->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'post_nominals')) {
                $table->string('post_nominals', 60)->nullable()->after('honorific');
            }
        });
        if (Schema::hasTable('signatory_invitations') && ! Schema::hasColumn('signatory_invitations', 'honorific')) {
            Schema::table('signatory_invitations', function (Blueprint $table) {
                $table->string('honorific', 40)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['honorific', 'post_nominals']);
        });
        if (Schema::hasColumn('signatory_invitations', 'honorific')) {
            Schema::table('signatory_invitations', fn (Blueprint $table) => $table->dropColumn('honorific'));
        }
    }
};
