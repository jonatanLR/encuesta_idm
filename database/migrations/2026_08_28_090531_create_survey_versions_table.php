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
        Schema::create('survey_versions', function (Blueprint $table) {
            $table->id();
            
             $table->foreignId('questionnaire_id')
                ->constrained('questionnaires')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('version', 20);

            $table->boolean('active')
                ->default(true);

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();

            $table->unique([
                'questionnaire_id',
                'version',
            ]);

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_versions');
    }
};
