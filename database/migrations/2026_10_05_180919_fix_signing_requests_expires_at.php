<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * On MySQL/MariaDB with explicit_defaults_for_timestamp OFF, the first NOT NULL timestamp column
 * silently gets "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP". For signing_requests that
 * column was expires_at, so every update (verify code, revoke, ...) reset the expiry to "now".
 * Making it explicitly NULL-able with a NULL default switches that behaviour off.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE signing_requests MODIFY expires_at TIMESTAMP NULL DEFAULT NULL');

        // Repair links that were still open: give them a fresh window from now.
        DB::table('signing_requests')
            ->where('status', 'pending')
            ->update(['expires_at' => now()->addDays((int) config('documate.workflow.external_expiry_days', 7))]);
    }

    public function down(): void
    {
        // Intentionally empty: restoring the buggy definition would be wrong.
    }
};