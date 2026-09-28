<?php

namespace Tests\Feature\Survey;

use App\Livewire\Survey\Form;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Models\Question;
use App\Models\Section;
use App\Models\QuestionType;

class SurveyFormAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermission(
        string $roleSlug,
        string $permissionSlug
    ): User {
        $role = Role::create([
            'name' => ucfirst(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
        ]);

        $permission = Permission::create([
            'name' => ucfirst(str_replace('.', ' ', $permissionSlug)),
            'slug' => $permissionSlug,
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create();

        $user->roles()->attach($role);

        return $user;
    }

    private function createUserWithPermissions(
        string $roleSlug,
        array $permissionSlugs
    ): User {
        $role = Role::create([
            'name' => ucfirst(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
        ]);

        $permissions = collect($permissionSlugs)->map(
            fn(string $permissionSlug) => Permission::create([
                'name' => ucfirst(str_replace('.', ' ', $permissionSlug)),
                'slug' => $permissionSlug,
            ])
        );

        $role->permissions()->attach($permissions->pluck('id'));

        $user = User::factory()->create();

        $user->roles()->attach($role);

        return $user;
    }

    public function test_user_with_survey_view_can_open_another_users_response(): void
    {
        $owner = User::factory()->create();

        $user = $this->createUserWithPermission(
            'field-user',
            'survey.view'
        );

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Form::class, [
            'response' => $response,
        ])->assertStatus(200);
    }

    public function test_user_without_survey_view_cannot_open_response(): void
    {
        $owner = User::factory()->create();

        $user = User::factory()->create();

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Form::class, [
            'response' => $response,
        ])->assertForbidden();
    }

    public function test_field_user_can_modify_own_response(): void
    {
        $user = $this->createUserWithPermissions(
            'field-user',
            ['survey.view', 'survey.update']
        );

        $response = SurveyResponse::factory()->create([
            'created_by' => $user->id,
        ]);

        $section = Section::factory()->create([
            'survey_version_id' => $response->survey_version_id,
        ]);

        $questionType = QuestionType::factory()->create([
            'code' => 'text',
            'name' => 'Texto',
        ]);

        $question = Question::factory()->create([
            'section_id' => $section->id,
            'question_type_id' => $questionType->id,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(Form::class, [
            'response' => $response,
        ])->call(
            'saveText',
            $question->id,
            'Texto de prueba'
        );

        $component->assertStatus(200);
    }

    public function test_field_user_cannot_modify_another_users_response(): void
    {
        $owner = User::factory()->create();

        $user = $this->createUserWithPermissions(
            'field-user',
            ['survey.view', 'survey.update']
        );

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $this->actingAs($user);

        Livewire::test(Form::class, [
            'response' => $response,
        ])->call('saveText', 1, 'Texto de prueba')
            ->assertForbidden();
    }
}
