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
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')
                ->constrained('questions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('label', 255);

            $table->string('value', 255);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'question_id',
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
        Schema::dropIfExists('question_options');
    }
};
