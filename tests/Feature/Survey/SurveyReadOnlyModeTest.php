<?php

namespace Tests\Feature\Survey;

use App\Livewire\Survey\Form;
use App\Livewire\Survey\Index;
use App\Models\Household;
use App\Models\HouseholdRelationship;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Section;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyReadOnlyModeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissionSlugs
     */
    private function createUserWithPermissions(string $roleSlug, array $permissionSlugs): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => ucfirst(str_replace('-', ' ', $roleSlug))]
        );

        $permissions = collect($permissionSlugs)->map(
            fn (string $permissionSlug) => Permission::query()->firstOrCreate(
                ['slug' => $permissionSlug],
                ['name' => ucfirst(str_replace('.', ' ', $permissionSlug))]
            )
        );

        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

        $user = User::factory()->create();

        $user->roles()->attach($role);

        return $user;
    }

    private function createFieldUser(): User
    {
        return $this->createUserWithPermissions(
            'field-user',
            ['survey.view', 'survey.create', 'survey.update', 'survey.complete']
        );
    }

    private function createSurveyAdmin(): User
    {
        return $this->createUserWithPermissions('survey-admin', ['survey.view']);
    }

    private function createResponseWithMemberSection(User $owner): SurveyResponse
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
        ]);

        return $response;
    }

    public function test_owner_edit_mode_shows_write_actions(): void
    {
        $owner = $this->createFieldUser();
        $response = $this->createResponseWithMemberSection($owner);

        $this->actingAs($owner);

        Livewire::test(Form::class, ['response' => $response])
            ->assertSet('readOnly', false)
            ->assertSet('canComplete', true)
            ->assertSee('Finalizar encuesta')
            ->assertDontSee('Modo consulta')
            ->call('goToSection', 1)
            ->assertSee('+ Agregar miembro');
    }

    public function test_owner_view_mode_is_read_only_and_offers_edit_link(): void
    {
        $owner = $this->createFieldUser();
        $response = $this->createResponseWithMemberSection($owner);

        $this->actingAs($owner);

        Livewire::withQueryParams(['modo' => 'lectura'])
            ->test(Form::class, ['response' => $response])
            ->assertSet('readOnly', true)
            ->assertSet('canComplete', false)
            ->assertSet('canEditFromReadOnly', true)
            ->assertSee('Modo consulta')
            ->assertSee('Editar encuesta')
            ->assertDontSee('Finalizar encuesta')
            ->call('goToSection', 1)
            ->assertDontSee('+ Agregar miembro');

        $this->assertSame(0, Household::query()->count());
    }

    public function test_other_users_survey_opens_read_only_without_write_actions(): void
    {
        $owner = $this->createFieldUser();
        $response = $this->createResponseWithMemberSection($owner);

        $viewer = $this->createSurveyAdmin();

        $this->actingAs($viewer);

        Livewire::test(Form::class, ['response' => $response])
            ->assertSet('readOnly', true)
            ->assertSet('canEditFromReadOnly', false)
            ->assertSee('Esta encuesta pertenece a otro usuario')
            ->assertDontSee('Editar encuesta')
            ->assertDontSee('Finalizar encuesta')
            ->call('nextSection')
            ->assertDontSee('+ Agregar miembro')
            ->assertSee('No hay miembros registrados en este hogar.');

        $this->assertSame(0, Household::query()->count());
    }

    public function test_index_shows_edit_only_for_own_surveys(): void
    {
        $owner = $this->createFieldUser();
        $response = SurveyResponse::factory()->create(['created_by' => $owner->id]);

        $editUrl = route('survey.form', ['response' => $response->id]);
        $viewUrl = route('survey.form', ['response' => $response->id, 'modo' => 'lectura']);

        $this->actingAs($owner);

        Livewire::test(Index::class)
            ->assertSee('+ Nueva encuesta')
            ->assertSeeHtml('href="'.$viewUrl.'"')
            ->assertSeeHtml('href="'.$editUrl.'"');

        $this->actingAs($this->createSurveyAdmin());

        Livewire::test(Index::class)
            ->assertDontSee('+ Nueva encuesta')
            ->assertSeeHtml('href="'.$viewUrl.'"')
            ->assertDontSeeHtml('href="'.$editUrl.'"');
    }

    public function test_logout_with_expired_page_redirects_to_login(): void
    {
        $request = Request::create('/logout', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        $response = $this->app->make(ExceptionHandler::class)
            ->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertSame(
            'Su sesión había expirado. Inicie sesión nuevamente.',
            $this->app['session.store']->get('status')
        );
    }

    public function test_expired_page_outside_logout_keeps_default_419(): void
    {
        $request = Request::create('/settings/profile', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        $response = $this->app->make(ExceptionHandler::class)
            ->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(419, $response->getStatusCode());
    }
}
