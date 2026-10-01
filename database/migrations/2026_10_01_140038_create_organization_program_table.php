<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'program_id']);
        });

        // Carry over the existing single program of each organization
        DB::table('organizations')->whereNotNull('program_id')->get(['id', 'program_id'])
            ->each(fn ($o) => DB::table('organization_program')->insert([
                'organization_id' => $o->id,
                'program_id' => $o->program_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('description')
                ->constrained('programs')->nullOnDelete();
        });

        // Only the first program can be kept when rolling back
        DB::table('organization_program')->orderBy('id')->get()->unique('organization_id')
            ->each(fn ($r) => DB::table('organizations')
                ->where('id', $r->organization_id)->update(['program_id' => $r->program_id]));

        Schema::dropIfExists('organization_program');
    }
};