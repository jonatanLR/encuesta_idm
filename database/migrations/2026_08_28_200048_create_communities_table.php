<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * Código tal como aparece en la fuente.
             * NO asumimos todavía su significado oficial.
             */
            $table->string('source_code', 30)->nullable();

            $table->string('name');

            $table->string('type', 30)
                ->default('other');

            /*
             * urban / rural / null
             *
             * El PDF no establece explícitamente esta
             * clasificación, por eso puede quedar NULL.
             */
            $table->string('area', 20)->nullable();

            $table->boolean('active')->default(true);

            $table->timestamps();

            $table->index('municipality_id');
            $table->index('name');
            $table->index('source_code');

            $table->unique([
                'municipality_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};