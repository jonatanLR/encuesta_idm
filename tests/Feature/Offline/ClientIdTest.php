<?php

use App\Models\Answer;
use App\Models\Question;
use App\Models\SurveyFile;
use App\Models\SurveyResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function clientIdMigration(): object
{
    return require database_path(
        'migrations/2026_11_01_072443_add_client_id_to_answers_and_survey_files_table.php'
    );
}

it('assigns a ULID client_id to new answers', function () {
    $answer = Answer::factory()->create();

    expect($answer->client_id)->toHaveLength(26)
        ->and(Str::isUlid($answer->client_id))->toBeTrue();
});

it('keeps the client_id sent by the client', function () {
    $clientId = (string) Str::ulid();

    $answer = Answer::factory()->create(['client_id' => $clientId]);

    expect($answer->fresh()->client_id)->toBe($clientId);
});

it('assigns a ULID client_id to new survey files', function () {
    $response = SurveyResponse::factory()->create();

    $file = SurveyFile::create([
        'survey_response_id' => $response->id,
        'file_type' => 'dni_front',
        'disk' => 'local',
        'path' => 'surveys/test/dni/front.jpg',
    ]);

    expect(Str::isUlid($file->client_id))->toBeTrue();
});

it('rejects duplicated answer client_ids', function () {
    $clientId = (string) Str::ulid();

    Answer::factory()->create(['client_id' => $clientId]);

    expect(fn () => Answer::factory()->create(['client_id' => $clientId]))
        ->toThrow(QueryException::class);
});

it('backfills existing rows without changing their ids or timestamps', function () {
    $migration = clientIdMigration();
    $migration->down();

    $response = SurveyResponse::factory()->create();
    $question = Question::factory()->create();
    $updatedAt = '2026-09-19 12:00:00';

    foreach (range(1, 3) as $index) {
        DB::table('answers')->insert([
            'survey_response_id' => $response->id,
            'question_id' => $question->id,
            'text_value' => "Respuesta {$index}",
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }

    DB::table('survey_files')->insert([
        'survey_response_id' => $response->id,
        'file_type' => 'image',
        'disk' => 'public',
        'path' => 'surveys/test/questions/foto.jpg',
        'created_at' => $updatedAt,
        'updated_at' => $updatedAt,
    ]);

    $answerIdsBefore = DB::table('answers')->orderBy('id')->pluck('id')->all();

    $migration->up();

    $answers = DB::table('answers')->orderBy('id')->get();
    $file = DB::table('survey_files')->first();

    expect($answers->pluck('id')->all())->toBe($answerIdsBefore)
        ->and($answers->pluck('client_id')->unique())->toHaveCount(3)
        ->and($answers->every(fn (object $answer): bool => Str::isUlid($answer->client_id)))->toBeTrue()
        ->and($answers->pluck('updated_at')->unique()->all())->toBe([$updatedAt])
        ->and(Str::isUlid($file->client_id))->toBeTrue()
        ->and($file->updated_at)->toBe($updatedAt);
});
