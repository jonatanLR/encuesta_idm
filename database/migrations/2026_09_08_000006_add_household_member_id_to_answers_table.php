<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('answers', 'household_member_id')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->foreignId('household_member_id')->nullable();
            });
        }

        $hasHouseholdMemberForeignKey = collect(
            Schema::getForeignKeys('answers')
        )->contains(function (array $foreignKey): bool {
            return $foreignKey['name'] === 'answers_household_member_id_foreign';
        });

        if (! $hasHouseholdMemberForeignKey) {
            Schema::table('answers', function (Blueprint $table) {
                $table->foreign('household_member_id')
                    ->references('id')
                    ->on('household_members')
                    ->cascadeOnDelete();
            });
        }

        $hasHouseholdMemberIndex = collect(
            Schema::getIndexes('answers')
        )->contains(function (array $index): bool {
            return $index['name']
                === 'answers_survey_response_id_question_id_household_member_id_index';
        });

        if (! $hasHouseholdMemberIndex) {
            Schema::table('answers', function (Blueprint $table) {
                $table->index([
                    'survey_response_id',
                    'question_id',
                    'household_member_id',
                ]);
            });
        }
    }

    public function down(): void
    {
        $hasHouseholdMemberIndex = collect(
            Schema::getIndexes('answers')
        )->contains(function (array $index): bool {
            return $index['name']
                === 'answers_survey_response_id_question_id_household_member_id_index';
        });

        if ($hasHouseholdMemberIndex) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropIndex(
                    'answers_survey_response_id_question_id_household_member_id_index'
                );
            });
        }

        $hasHouseholdMemberForeignKey = collect(
            Schema::getForeignKeys('answers')
        )->contains(function (array $foreignKey): bool {
            return $foreignKey['name'] === 'answers_household_member_id_foreign';
        });

        if ($hasHouseholdMemberForeignKey) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropForeign('answers_household_member_id_foreign');
            });
        }

        if (Schema::hasColumn('answers', 'household_member_id')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropColumn('household_member_id');
            });
        }
    }
};
