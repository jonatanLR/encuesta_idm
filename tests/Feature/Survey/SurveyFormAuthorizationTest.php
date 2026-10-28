<?php

namespace Tests\Feature\Survey;

use App\Livewire\Survey\Form;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use App\Models\Permission;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Role;
use App\Models\Section;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

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
            fn (string $permissionSlug) => Permission::create([
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

    /**
     * Crea una encuesta con las secciones GENERAL y MEMBER y el catálogo
     * de relaciones necesario para el alta dinámica de miembros.
     *
     * @return array{response: SurveyResponse, relationship: HouseholdRelationship}
     */
    private function createResponseWithMemberSection(User $owner): array
    {
        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        Section::factory()->create([
            'survey_version_id' => $response->survey_version_id,
            'code' => 'GENERAL',
            'sort_order' => 1,
        ]);

        Section::factory()->create([
            'survey_version_id' => $response->survey_version_id,
            'code' => 'MEMBER',
            'sort_order' => 2,
        ]);

        HouseholdRelationship::factory()->create([
            'code' => 'HEAD',
            'name' => 'Jefe/a de hogar',
            'sort_order' => 1,
        ]);

        $relationship = HouseholdRelationship::factory()->create([
            'code' => 'CHILD',
            'name' => 'Hijo/a',
            'sort_order' => 2,
        ]);

        return [
            'response' => $response,
            'relationship' => $relationship,
        ];
    }

    public function test_user_without_update_permission_cannot_add_member(): void
    {
        $owner = User::factory()->create();

        $viewer = $this->createUserWithPermission(
            'field-user',
            'survey.view'
        );

        ['response' => $response, 'relationship' => $relationship] =
            $this->createResponseWithMemberSection($owner);

        $this->actingAs($viewer);

        Livewire::test(Form::class, [
            'response' => $response,
        ])
            ->set('newMemberName', 'Miembro no autorizado')
            ->set('newMemberRelationshipId', $relationship->id)
            ->call('addMember')
            ->assertForbidden();

        $this->assertSame(0, Household::query()->count());
        $this->assertSame(0, HouseholdMember::query()->count());
    }

    public function test_navigating_to_member_section_without_update_permission_does_not_create_household(): void
    {
        $owner = User::factory()->create();

        $viewer = $this->createUserWithPermission(
            'field-user',
            'survey.view'
        );

        ['response' => $response] =
            $this->createResponseWithMemberSection($owner);

        $this->actingAs($viewer);

        Livewire::test(Form::class, [
            'response' => $response,
        ])
            ->call('nextSection')
            ->assertSet('currentSectionIndex', 1)
            ->call('goToSection', 0)
            ->call('goToSection', 1)
            ->assertSet('currentSectionIndex', 1)
            ->assertStatus(200);

        $this->assertSame(0, Household::query()->count());
        $this->assertSame(0, HouseholdMember::query()->count());
    }

    public function test_owner_navigating_to_member_section_creates_household_and_head(): void
    {
        $owner = $this->createUserWithPermissions(
            'field-user',
            ['survey.view', 'survey.update']
        );

        ['response' => $response] =
            $this->createResponseWithMemberSection($owner);

        $this->actingAs($owner);

        Livewire::test(Form::class, [
            'response' => $response,
        ])
            ->call('nextSection')
            ->assertSet('currentSectionIndex', 1);

        $household = Household::query()
            ->where('survey_response_id', $response->id)
            ->first();

        $this->assertNotNull($household);
        $this->assertSame(1, $household->householdMembers()->count());
        $this->assertSame(
            'HEAD',
            $household->householdMembers()->first()->householdRelationship->code
        );
    }

    public function test_owner_can_add_member(): void
    {
        $owner = $this->createUserWithPermissions(
            'field-user',
            ['survey.view', 'survey.update']
        );

        ['response' => $response, 'relationship' => $relationship] =
            $this->createResponseWithMemberSection($owner);

        $this->actingAs($owner);

        Livewire::test(Form::class, [
            'response' => $response,
        ])
            ->call('goToSection', 1)
            ->set('newMemberName', 'Hijo de prueba')
            ->set('newMemberRelationshipId', $relationship->id)
            ->call('addMember')
            ->assertHasNoErrors();

        $this->assertSame(2, HouseholdMember::query()->count());
        $this->assertTrue(
            HouseholdMember::query()
                ->where('name', 'Hijo de prueba')
                ->where('relationship_id', $relationship->id)
                ->exists()
        );
    }
}
