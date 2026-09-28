<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logbook_uploads', function (Blueprint $table) {
            $table->id();                                        // bigint(20) unsigned, auto increment, primary
            $table->unsignedBigInteger('uploaded_by')->index();  // bigint(20) unsigned, indexed
            $table->string('original_filename', 255);            // varchar(255)
            $table->json('headers');                             // longtext utf8mb4_bin (JSON on MariaDB)
            $table->unsignedInteger('row_count')->default(0);    // int(10) unsigned, default 0
            $table->unsignedInteger('column_count')->default(0); // int(10) unsigned, default 0
            $table->timestamp('uploaded_at')->useCurrent();      // default current_timestamp()
            $table->timestamps();                                // created_at, updated_at (nullable)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_uploads');
    }
};