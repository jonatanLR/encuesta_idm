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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_version_id')
                ->constrained('survey_versions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('sections')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('name', 150);

            $table->string('description', 500)
                ->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'survey_version_id',
                'sort_order',
            ]);

            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
