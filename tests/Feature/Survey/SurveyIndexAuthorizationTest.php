<?php

namespace Tests\Feature\Survey;

use App\Livewire\Survey\Index;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyIndexAuthorizationTest extends TestCase
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

    public function test_user_with_survey_view_can_render_survey_index(): void
    {
        $user = $this->createUserWithPermission(
            'field-user',
            'survey.view'
        );

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertStatus(200);
    }

    public function test_user_without_survey_view_cannot_render_survey_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertForbidden();
    }

    public function test_user_without_survey_create_cannot_start_survey(): void
    {
        $user = $this->createUserWithPermission(
            'field-user',
            'survey.view'
        );

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('startSurvey')
            ->assertForbidden();
    }
}
