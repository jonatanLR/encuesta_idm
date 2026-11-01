<?php

use App\Models\Answer;
use App\Models\Community;
use App\Models\Question;
use App\Models\Section;
use App\Models\SurveyResponse;
use App\Models\SurveyVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('reports a clean database without findings', function () {
    $this->artisan('offline:preflight')
        ->expectsOutputToContain('Revisiones con hallazgos: 0')
        ->expectsOutputToContain('No se modificó ningún dato.')
        ->assertSuccessful();
});

it('reports duplicates, historical member answers and test data without modifying them', function () {
    $version = SurveyVersion::factory()->create(['published_at' => now()]);

    $memberSection = Section::factory()->create([
        'survey_version_id' => $version->id,
        'code' => 'MEMBER',
    ]);

    $memberNameQuestion = Question::factory()->create([
        'section_id' => $memberSection->id,
        'code' => 'MEMBER_001',
    ]);

    Question::factory()->create([
        'section_id' => $memberSection->id,
        'code' => 'ADMIN_TEST_001',
        'active' => true,
    ]);

    $response = SurveyResponse::factory()->create([
        'survey_version_id' => $version->id,
    ]);

    Answer::factory()->count(2)->create([
        'survey_response_id' => $response->id,
        'question_id' => $memberNameQuestion->id,
        'household_member_id' => null,
    ]);

    Community::factory()->create([
        'source_code' => 'TEST-001',
        'active' => true,
    ]);

    $answersBefore = DB::table('answers')->get()->toJson();

    $this->artisan('offline:preflight --detalle')
        ->expectsOutputToContain('Respuestas duplicadas (encuesta + pregunta + miembro)')
        ->expectsOutputToContain('Revisiones con hallazgos: 4')
        ->assertSuccessful();

    expect(DB::table('answers')->get()->toJson())->toBe($answersBefore);
});

it('does not count checks that are not applicable as findings', function () {
    Schema::table('answers', function (Blueprint $table): void {
        $table->dropUnique(['client_id']);
        $table->dropColumn('client_id');
    });

    $this->artisan('offline:preflight')
        ->expectsOutputToContain('Columna client_id aún no existe (migración pendiente).')
        ->expectsOutputToContain('Revisiones con hallazgos: 0')
        ->assertSuccessful();
});
