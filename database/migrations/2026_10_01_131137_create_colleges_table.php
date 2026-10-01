<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Names match the strings already stored in users.college / used for OCR matching.
        foreach ([
            ['CAS', 'College of Arts and Sciences'],
            ['CME', 'College of Management and Entrepreneurship'],
            ['COE', 'College of Education'],
        ] as [$code, $name]) {
            DB::table('colleges')->insert([
                'code' => $code, 'name' => $name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};