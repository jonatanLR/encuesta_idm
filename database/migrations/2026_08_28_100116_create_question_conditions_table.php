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
        Schema::create('question_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('depends_on_question_id')
                ->constrained('questions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('depends_on_option_id')
                ->nullable()
                ->constrained('question_options')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('operator', 30)
                ->default('equals');

            $table->string('expected_value', 255)
                ->nullable();

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->index('question_id');
            $table->index('depends_on_question_id');
            $table->index('depends_on_option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_conditions');
    }
};
