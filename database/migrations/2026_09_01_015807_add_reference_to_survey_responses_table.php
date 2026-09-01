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
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->after('status');
        });

        $responses = DB::table('survey_responses')
            ->where(function ($query) {
                $query->whereNull('reference')
                    ->orWhere('reference', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($responses as $response) {
            DB::table('survey_responses')
                ->where('id', $response->id)
                ->update([
                    'reference' => 'ENC-' . now()->format('Ymd') . '-' . str_pad((string) $response->id, 4, '0', STR_PAD_LEFT),
                ]);
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique('reference', 'survey_responses_reference_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique('survey_responses_reference_unique');
            $table->dropColumn('reference');
        });
    }
};