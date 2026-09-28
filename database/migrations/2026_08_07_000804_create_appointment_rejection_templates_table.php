<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_rejection_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('message');
            $table->timestamps();
        });

        DB::table('appointment_rejection_templates')->insert([
            ['label' => 'Missing requirements', 'message' => 'Your appointment was rejected because required documents/attachments are missing. Please prepare them and book a new slot.', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Incorrect information', 'message' => 'The information provided in your document does not match our records. Please review and resubmit.', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Duplicate request', 'message' => 'You already have an active transaction for this document type.', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'Office fully booked', 'message' => 'The selected slot is no longer available. Please choose another date or time.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_rejection_templates');
    }
};