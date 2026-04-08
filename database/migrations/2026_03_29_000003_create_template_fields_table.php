<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id('field_id');

            $table->unsignedBigInteger('version_id');

            $table->string('label');
            $table->string('name')->nullable()->change();

            $table->enum('source_type', ['system', 'input']);
            $table->string('system_key')->nullable();

            $table->enum('data_type', ['text', 'number', 'date']);
            $table->enum('field_type', ['single', 'paragraph']);

            // Position & Size
            $table->float('x_position');
            $table->float('y_position');
            $table->float('width');
            $table->float('height');

            // Typography
            $table->string('font_family')->default('Arial');
            $table->string('font_weight')->default('normal');
            $table->integer('font_size')->default(12);
            $table->string('text_color')->default('#000000');
            $table->string('alignment')->default('left');

            // Paragraph settings
            $table->float('line_height')->nullable();
            $table->float('letter_spacing')->nullable();

            // Constraints
            $table->integer('max_length')->nullable();
            $table->integer('max_lines')->nullable();
            $table->boolean('required')->default(false);

            // Extras (IMPORTANT)
            $table->string('placeholder')->nullable();
            $table->integer('z_index')->default(1);

            $table->timestamps();

            // FK
            $table->foreign('version_id')
                ->references('version_id')->on('template_versions')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
