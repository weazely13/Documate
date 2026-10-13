<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('signatory_roles', 'scope')) {
            Schema::table('signatory_roles', function (Blueprint $t) {
                $t->string('scope', 20)->default('none')->after('description'); // none | organization | program | college
            });
        }

        if (! Schema::hasColumn('signatory_assignments', 'scope_type')) {
            Schema::table('signatory_assignments', function (Blueprint $t) {
                $t->string('scope_type', 20)->nullable();
                $t->unsignedBigInteger('scope_id')->nullable();
            });
        }

        if (! Schema::hasIndex('signatory_assignments', 'sig_assign_role_scope_idx')) {
            Schema::table('signatory_assignments', function (Blueprint $t) {
                $t->index(['signatory_role_id', 'scope_type', 'scope_id'], 'sig_assign_role_scope_idx');
            });
        }

        if (! Schema::hasColumn('transaction_approvals', 'scope_type')) {
            Schema::table('transaction_approvals', function (Blueprint $t) {
                $t->string('scope_type', 20)->nullable();
                $t->unsignedBigInteger('scope_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaction_approvals', 'scope_type')) {
            Schema::table('transaction_approvals', function (Blueprint $t) {
                $t->dropColumn(['scope_type', 'scope_id']);
            });
        }

        if (Schema::hasIndex('signatory_assignments', 'sig_assign_role_scope_idx')) {
            Schema::table('signatory_assignments', function (Blueprint $t) {
                $t->dropIndex('sig_assign_role_scope_idx');
            });
        }

        if (Schema::hasColumn('signatory_assignments', 'scope_type')) {
            Schema::table('signatory_assignments', function (Blueprint $t) {
                $t->dropColumn(['scope_type', 'scope_id']);
            });
        }

        if (Schema::hasColumn('signatory_roles', 'scope')) {
            Schema::table('signatory_roles', function (Blueprint $t) {
                $t->dropColumn('scope');
            });
        }
    }
};