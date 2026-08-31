<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('questionnaire_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('survey_version_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('community_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 20)
                ->default('draft');

            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'questionnaire_id',
                'survey_version_id',
            ]);

            $table->index([
                'community_id',
                'status',
            ]);

            $table->index([
                'created_by',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};