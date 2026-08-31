<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('survey_response_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained()
                ->restrictOnDelete();

            $table->text('text_value')
                ->nullable();

            $table->decimal('number_value', 15, 4)
                ->nullable();

            $table->date('date_value')
                ->nullable();

            $table->foreignId('option_id')
                ->nullable()
                ->constrained('question_options')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index([
                'survey_response_id',
                'question_id',
            ]);

            $table->index([
                'question_id',
                'option_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};