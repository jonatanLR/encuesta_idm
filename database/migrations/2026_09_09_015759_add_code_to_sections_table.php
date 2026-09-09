<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('code', 30)->nullable();
        });

        $rootCodes = [
            1 => 'GENERAL',
            2 => 'INFORMANT',
            3 => 'HOUSING',
            4 => 'HOUSEHOLD',
            5 => 'MEMBER',
            6 => 'CLOSURE',
        ];

        $surveyVersionIds = DB::table('sections')
            ->distinct()
            ->pluck('survey_version_id');

        foreach ($surveyVersionIds as $surveyVersionId) {
            foreach ($rootCodes as $sortOrder => $code) {
                DB::table('sections')
                    ->where('survey_version_id', $surveyVersionId)
                    ->whereNull('parent_id')
                    ->where('sort_order', $sortOrder)
                    ->update(['code' => $code]);
            }

            $housingId = DB::table('sections')
                ->where('survey_version_id', $surveyVersionId)
                ->whereNull('parent_id')
                ->where('sort_order', 3)
                ->value('id');

            DB::table('sections')
                ->where('survey_version_id', $surveyVersionId)
                ->where('parent_id', $housingId)
                ->where('sort_order', 1)
                ->update(['code' => 'LOCAL']);
        }

        Schema::table('sections', function (Blueprint $table) {
            $table->string('code', 30)->nullable(false)->change();
            $table->unique(['survey_version_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['survey_version_id', 'code']);
            $table->dropColumn('code');
        });
    }
};
