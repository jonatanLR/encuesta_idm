<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_source', 20)->nullable();
        });

        DB::table('survey_responses')
            ->whereNull('public_id')
            ->orderBy('id')
            ->select('id')
            ->get()
            ->each(function (object $response): void {
                DB::table('survey_responses')
                    ->where('id', $response->id)
                    ->update(['public_id' => (string) Str::ulid()]);
            });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('public_id', 26)->nullable(false)->change();
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn([
                'public_id',
                'latitude',
                'longitude',
                'location_source',
            ]);
        });
    }
};
