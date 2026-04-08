<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_verifications')) {
            Schema::create('student_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('student_number')->nullable();
                $table->string('e_slip_path')->nullable();
                $table->json('ocr_data')->nullable();
                $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
                $table->string('semester');
                $table->string('academic_year');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('student_verifications', function (Blueprint $table) {
            if (!Schema::hasColumn('student_verifications', 'student_number')) {
                $table->string('student_number')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('student_verifications', 'e_slip_path')) {
                $table->string('e_slip_path')->nullable()->after('student_number');
            }

            if (!Schema::hasColumn('student_verifications', 'ocr_data')) {
                $table->json('ocr_data')->nullable()->after('e_slip_path');
            }
        });

        if (
            Schema::hasColumn('student_verifications', 'ocr_extracted_data') &&
            Schema::hasColumn('student_verifications', 'ocr_data')
        ) {
            DB::table('student_verifications')
                ->whereNull('ocr_data')
                ->update(['ocr_data' => DB::raw('ocr_extracted_data')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_verifications')) {
            return;
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('student_verifications', 'student_number') ? 'student_number' : null,
            Schema::hasColumn('student_verifications', 'ocr_data') ? 'ocr_data' : null,
        ]));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('student_verifications', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};
