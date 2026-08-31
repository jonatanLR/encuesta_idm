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
        Schema::table('questions', function (Blueprint $table) {
            //
            $table->string('data_source', 30)
                ->nullable()
                ->after('question_type_id');

            $table->string('data_source_table', 100)
                ->nullable()
                ->after('data_source');

            $table->index([
                'data_source',
                'data_source_table',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            //
             Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex([
                'questions_data_source_data_source_table_index',
            ]);

            $table->dropColumn([
                'data_source',
                'data_source_table',
            ]);
        });
        });
    }
};
