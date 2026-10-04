<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('logbook_uploads', function (Blueprint $table) {
			$table->id();
			$table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
			$table->string('original_filename');
			$table->json('headers');
			$table->unsignedInteger('row_count');
			$table->unsignedInteger('column_count');
			$table->timestamp('uploaded_at');
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('logbook_uploads');
	}
};