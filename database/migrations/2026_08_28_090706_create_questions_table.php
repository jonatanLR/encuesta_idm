<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('sections')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('question_type_id')
                ->constrained('question_types')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('code', 50);

            $table->text('label');

            $table->text('description')
                ->nullable();

            $table->boolean('required')
                ->default(false);

            $table->boolean('active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->unique([
                'section_id',
                'code',
            ]);

            $table->index([
                'section_id',
                'sort_order',
            ]);

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
