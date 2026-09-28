<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officer_positions', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique(); // e.g. President, Vice President, Auditor
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed sensible defaults so the admin isn't starting from an empty list.
        $defaults = [
            'President', 'Vice President', 'Secretary', 'Assistant Secretary',
            'Treasurer', 'Auditor', 'Public Information Officer', 'Sergeant-at-Arms',
        ];

        foreach ($defaults as $i => $title) {
            \DB::table('officer_positions')->insert([
                'title' => $title,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('officer_positions');
    }
};