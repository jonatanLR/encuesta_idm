<?php

use App\Livewire\Survey\Form;
use App\Models\Answer;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Permission;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Role;
use App\Models\Section;
use App\Models\SurveyFile;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\SurveyFileStorage;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * @param  array<int, string>  $permissionSlugs
 */
function privateFilesUser(array $permissionSlugs): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => 'field-user'],
        ['name' => 'Field user']
    );

    $permissions = collect($permissionSlugs)->map(
        fn (string $slug) => Permission::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $slug]
        )
    );

    $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

/**
 * @return array{0: SurveyResponse, 1: Question}
 */
function surveyWithImageQuestion(User $owner): array
{
    $response = SurveyResponse::factory()->create(['created_by' => $owner->id]);

    $section = Section::factory()->create([
        'survey_version_id' => $response->survey_version_id,
        'code' => 'HOUSING',
    ]);

    $question = Question::factory()->create([
        'section_id' => $section->id,
        'code' => 'HOUSING_PHOTO',
        'question_type_id' => QuestionType::factory()->create(['code' => 'image'])->id,
    ]);

    return [$response, $question];
}

function storedSurveyFile(SurveyResponse $response, string $disk, string $path): SurveyFile
{
    Storage::disk($disk)->put($path, 'contenido-de-prueba');

    return SurveyFile::create([
        'survey_response_id' => $response->id,
        'file_type' => 'image',
        'disk' => $disk,
        'path' => $path,
        'original_name' => 'foto.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 19,
    ]);
}

describe('ruta autenticada de fotografías', function () {
    it('redirects guests to login', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'local', 'surveys/a/foto.jpg');

        $this->get(route('survey-files.show', $file))->assertRedirect(route('login'));
    });

    it('forbids users without survey.view', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'local', 'surveys/a/foto.jpg');

        $this->actingAs(User::factory()->create())
            ->get(route('survey-files.show', $file))
            ->assertForbidden();
    });

    it('serves a private file to users who can view the survey', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'local', 'surveys/a/foto.jpg');

        $response = $this->actingAs(privateFilesUser(['survey.view']))
            ->get(route('survey-files.show', $file))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        expect($response->streamedContent())->toBe('contenido-de-prueba')
            ->and($response->headers->get('Cache-Control'))->toContain('private');
    });

    it('still serves legacy files from the public disk', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'public', 'surveys/a/legado.jpg');

        $response = $this->actingAs(privateFilesUser(['survey.view']))
            ->get(route('survey-files.show', $file))
            ->assertOk();

        expect($response->streamedContent())->toBe('contenido-de-prueba');
    });

    it('returns 404 when the physical file is missing or the disk is not allowed', function () {
        $user = privateFilesUser(['survey.view']);
        $response = SurveyResponse::factory()->create();

        $missing = storedSurveyFile($response, 'local', 'surveys/a/foto.jpg');
        Storage::disk('local')->delete($missing->path);

        $otherDisk = storedSurveyFile($response, 'local', 'surveys/a/otra.jpg');
        $otherDisk->forceFill(['disk' => 's3'])->save();

        $this->actingAs($user)->get(route('survey-files.show', $missing))->assertNotFound();
        $this->actingAs($user)->get(route('survey-files.show', $otherDisk))->assertNotFound();
    });
});

describe('carga de fotografías en el formulario', function () {
    it('stores image answers on the private disk and links them through the route', function () {
        $user = privateFilesUser(['survey.view', 'survey.update']);
        [$response, $question] = surveyWithImageQuestion($user);

        $component = Livewire::actingAs($user)
            ->test(Form::class, ['response' => $response])
            ->set("imageFiles.{$question->id}", UploadedFile::fake()->image('vivienda.jpg'))
            ->assertHasNoErrors();

        $file = SurveyFile::query()->sole();

        expect($file->disk)->toBe('local')
            ->and($file->path)->toStartWith("surveys/{$response->public_id}/questions/HOUSING_PHOTO/");

        Storage::disk('local')->assertExists($file->path);
        expect(Storage::disk('public')->allFiles())->toBe([]);

        $component->assertSeeHtml($file->url())
            ->assertDontSeeHtml('/storage/surveys');
    });

    it('replaces a legacy public photo safely', function () {
        $user = privateFilesUser(['survey.view', 'survey.update']);
        [$response, $question] = surveyWithImageQuestion($user);

        $answer = Answer::factory()->create([
            'survey_response_id' => $response->id,
            'question_id' => $question->id,
            'household_member_id' => null,
        ]);

        $legacy = storedSurveyFile($response, 'public', 'surveys/legado/foto.jpg');
        $legacy->update(['answer_id' => $answer->id]);

        Livewire::actingAs($user)
            ->test(Form::class, ['response' => $response])
            ->set("imageFiles.{$question->id}", UploadedFile::fake()->image('nueva.jpg'))
            ->assertHasNoErrors();

        $file = SurveyFile::query()->sole();

        expect($file->id)->not->toBe($legacy->id)
            ->and($file->disk)->toBe('local')
            ->and($file->answer_id)->toBe($answer->id);

        Storage::disk('public')->assertMissing('surveys/legado/foto.jpg');
        Storage::disk('local')->assertExists($file->path);
    });

    it('stores DNI photos on the private disk and replaces the previous one', function () {
        $user = privateFilesUser(['survey.view', 'survey.update']);
        $response = SurveyResponse::factory()->create(['created_by' => $user->id]);
        $household = Household::factory()->create(['survey_response_id' => $response->id]);
        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'capture_started_at' => now(),
        ]);

        $component = Livewire::actingAs($user)
            ->test(Form::class, ['response' => $response])
            ->set('dniFrontPhoto', UploadedFile::fake()->image('dni-1.jpg'))
            ->assertHasNoErrors();

        $first = SurveyFile::query()->sole();

        $component->set('dniFrontPhoto', UploadedFile::fake()->image('dni-2.jpg'))
            ->assertHasNoErrors();

        $second = SurveyFile::query()->sole();

        expect($second->id)->not->toBe($first->id)
            ->and($second->disk)->toBe('local')
            ->and($second->file_type)->toBe('dni_front');

        Storage::disk('local')->assertMissing($first->path);
        Storage::disk('local')->assertExists($second->path);
    });

    it('keeps the previous photo when saving the new record fails', function () {
        $response = SurveyResponse::factory()->create();
        $previous = storedSurveyFile($response, 'local', 'surveys/a/anterior.jpg');

        expect(fn () => app(SurveyFileStorage::class)->store(
            file: UploadedFile::fake()->image('nueva.jpg'),
            directory: 'surveys/a',
            attributes: [
                'survey_response_id' => $response->id,
                'file_type' => null,
            ],
            replacing: $previous,
        ))->toThrow(QueryException::class);

        expect(SurveyFile::query()->sole()->is($previous))->toBeTrue()
            ->and(Storage::disk('local')->allFiles('surveys/a'))->toBe(['surveys/a/anterior.jpg']);
    });
});

describe('comando offline:move-files-private', function () {
    it('changes nothing with --dry-run', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'public', 'surveys/a/foto.jpg');

        $this->artisan('offline:move-files-private', ['--dry-run' => true])
            ->expectsOutputToContain('Simulación')
            ->assertSuccessful();

        expect($file->fresh()->disk)->toBe('public');
        Storage::disk('public')->assertExists('surveys/a/foto.jpg');
        Storage::disk('local')->assertMissing('surveys/a/foto.jpg');
    });

    it('moves public files to the private disk without touching updated_at and is idempotent', function () {
        $file = storedSurveyFile(SurveyResponse::factory()->create(), 'public', 'surveys/a/b/foto.jpg');
        $updatedAt = $file->updated_at->toDateTimeString();

        $this->travel(1)->hour();

        $this->artisan('offline:move-files-private')->assertSuccessful();

        $file->refresh();

        expect($file->disk)->toBe('local')
            ->and($file->updated_at->toDateTimeString())->toBe($updatedAt)
            ->and(Storage::disk('local')->get('surveys/a/b/foto.jpg'))->toBe('contenido-de-prueba')
            ->and(Storage::disk('public')->allFiles())->toBe([])
            ->and(Storage::disk('public')->allDirectories())->toBe([]);

        $this->artisan('offline:move-files-private')->assertSuccessful();

        expect($file->fresh()->disk)->toBe('local');
        Storage::disk('local')->assertExists('surveys/a/b/foto.jpg');
    });

    it('relinks records already copied and leaves missing files untouched', function () {
        $response = SurveyResponse::factory()->create();

        $copied = storedSurveyFile($response, 'public', 'surveys/a/copiada.jpg');
        Storage::disk('local')->put('surveys/a/copiada.jpg', 'contenido-de-prueba');
        Storage::disk('public')->delete('surveys/a/copiada.jpg');

        $missing = storedSurveyFile($response, 'public', 'surveys/a/perdida.jpg');
        Storage::disk('public')->delete('surveys/a/perdida.jpg');

        $this->artisan('offline:move-files-private')
            ->expectsOutputToContain('No existe en ningún disco')
            ->assertSuccessful();

        expect($copied->fresh()->disk)->toBe('local')
            ->and($missing->fresh()->disk)->toBe('public');
    });
});
