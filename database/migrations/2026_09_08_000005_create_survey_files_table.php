<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('answer_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('household_member_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('file_type');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index('survey_response_id');
            $table->index('answer_id');
            $table->index('household_member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_files');
    }
};
