<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Switches the free-text `organization` / `program` fields on users to proper
 * foreign keys against the new `organizations` / `programs` tables, so that
 * profile edit + registration can render them as dropdowns.
 *
 * The old free-text values are kept (renamed) as `legacy_organization` /
 * `legacy_program` purely for reference/audit — nothing reads them after
 * this migration. You can drop them later once you've confirmed the data
 * migrated cleanly.
 *
 * NOTE: renameColumn() requires doctrine/dbal on Laravel versions that
 * still depend on it. If `composer require doctrine/dbal` is needed in
 * your app, do that before running this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'organization') && ! Schema::hasColumn('users', 'legacy_organization')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('organization', 'legacy_organization');
            });
        }

        if (Schema::hasColumn('users', 'program') && ! Schema::hasColumn('users', 'legacy_program')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('program', 'legacy_program');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('legacy_organization')
                    ->constrained('organizations')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('legacy_program')
                    ->constrained('programs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'organization_id')) {
                $table->dropConstrainedForeignId('organization_id');
            }
            if (Schema::hasColumn('users', 'program_id')) {
                $table->dropConstrainedForeignId('program_id');
            }
        });

        if (Schema::hasColumn('users', 'legacy_organization')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('legacy_organization', 'organization');
            });
        }

        if (Schema::hasColumn('users', 'legacy_program')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('legacy_program', 'program');
            });
        }
    }
};