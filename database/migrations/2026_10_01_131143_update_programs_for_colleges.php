<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master's programs are no longer supported. users.program_id is nullOnDelete,
        // so affected students simply end up with no program.
        if (Schema::hasColumn('programs', 'level')) {
            DB::table('programs')->where('level', 'master')->delete();
        }

        Schema::table('programs', function (Blueprint $table) {
            if (! Schema::hasColumn('programs', 'college_id')) {
                // nullable so existing bachelor programs survive; admin assigns their college afterwards
                $table->foreignId('college_id')->nullable()->after('name')
                    ->constrained('colleges')->nullOnDelete();
            }
            if (Schema::hasColumn('programs', 'level')) {
                $table->dropColumn('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (! Schema::hasColumn('programs', 'level')) {
                $table->enum('level', ['bachelor', 'master'])->default('bachelor');
            }
            if (Schema::hasColumn('programs', 'college_id')) {
                $table->dropConstrainedForeignId('college_id');
            }
        });
    }
};