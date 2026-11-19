<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Issue reports are gone: Feedback is the only kind of report. Deletes the issue reports (and their attached
 * images) and drops the columns only they used, `type` and `severity`. Databases created after the create
 * migration was trimmed never had these columns, so each step checks first.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('feedback_reports', 'type')) {
            return;
        }

        $issues = DB::table('feedback_reports')->where('type', 'issue');

        $attachments = (clone $issues)->whereNotNull('attachment_path')->pluck('attachment_path')->all();
        if ($attachments) {
            Storage::disk('local')->delete($attachments);
        }

        $issues->delete();

        Schema::table('feedback_reports', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
        });

        Schema::table('feedback_reports', function (Blueprint $table) {
            $table->dropColumn(Schema::hasColumn('feedback_reports', 'severity') ? ['type', 'severity'] : ['type']);
        });
    }

    public function down(): void
    {
        // The deleted issue reports cannot be brought back; only the columns are restored.
        Schema::table('feedback_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('feedback_reports', 'type')) {
                $table->string('type', 16)->default('feedback')->after('user_id');
            }
            if (! Schema::hasColumn('feedback_reports', 'severity')) {
                $table->string('severity', 16)->nullable()->after('rating');
            }
        });
    }
};
