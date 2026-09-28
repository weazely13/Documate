<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logbook_upload_id')->constrained('logbook_uploads')->cascadeOnDelete();
            $table->unsignedInteger('row_number'); // position within the source excel file

            // Known VPSD logbook columns (mirrors the sample excel headers).
            // Kept nullable/string since source data is hand-typed and inconsistent.
            $table->string('entry_date')->nullable();
            $table->string('entry_time')->nullable();
            $table->string('applicant_name')->nullable();
            $table->string('program')->nullable();
            $table->string('address')->nullable();
            $table->string('issued_to')->nullable();
            $table->string('relation')->nullable();
            $table->string('purpose')->nullable();
            $table->string('released_at')->nullable();

            // Full row exactly as parsed (header label => cell value), so nothing is lost
            // even if a future upload has extra/renamed columns.
            $table->json('raw_data');

            $table->timestamps();

            $table->index(['logbook_upload_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_entries');
    }
};