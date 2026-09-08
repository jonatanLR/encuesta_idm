<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('household_members', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();
            $table->foreignId('household_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('relationship_id')
                ->constrained('household_relationships')
                ->restrictOnDelete();
            $table->string('name');
            $table->decimal('age', 5, 2)->nullable();
            $table->string('sex')->nullable();
            $table->string('dni')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['household_id', 'relationship_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_members');
    }
};
