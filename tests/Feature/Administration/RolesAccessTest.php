<?php

use App\Livewire\Administration\Roles\Index as RolesIndex;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

it('discovers the role policy automatically', function () {
    expect(Gate::getPolicyFor(Role::class))->toBeInstanceOf(RolePolicy::class);
});

it('grants role policy abilities only to admins', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);
    $fieldUserRole = Role::create([
        'name' => 'Field user',
        'slug' => 'field-user',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);
    $fieldUser = User::factory()->create();
    $fieldUser->roles()->attach($fieldUserRole);
    $role = Role::create([
        'name' => 'Custom role',
        'slug' => 'custom-role',
    ]);

    $policy = new RolePolicy;

    expect($policy->viewAny($admin))->toBeTrue()
        ->and($policy->view($admin, $role))->toBeTrue()
        ->and($policy->create($admin))->toBeTrue()
        ->and($policy->update($admin, $role))->toBeTrue()
        ->and($policy->viewAny($fieldUser))->toBeFalse()
        ->and($policy->view($fieldUser, $role))->toBeFalse()
        ->and($policy->create($fieldUser))->toBeFalse()
        ->and($policy->update($fieldUser, $role))->toBeFalse();
});

it('allows admin to open roles administration', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin)
        ->get(route('administration.roles'))
        ->assertOk()
        ->assertSee('Roles');
});

it('returns 403 when a non-admin role opens roles administration', function (string $roleSlug) {
    $role = Role::create([
        'name' => $roleSlug,
        'slug' => $roleSlug,
    ]);

    $user = User::factory()->create();
    $user->roles()->attach($role);

    $this->actingAs($user)
        ->get(route('administration.roles'))
        ->assertForbidden();
})->with([
    'survey-admin',
    'field-user',
]);

it('defaults new roles to active as a boolean', function () {
    Role::create([
        'name' => 'Custom role',
        'slug' => 'custom-role',
    ]);

    $role = Role::query()->firstOrFail();

    expect($role->active)->toBeTrue();
});

it('renders roles with their slugs, active states, and user counts', function () {
    $adminRole = Role::create([
        'name' => 'Administrador del sistema',
        'slug' => 'admin',
    ]);
    $fieldUserRole = Role::create([
        'name' => 'Usuario de campo',
        'slug' => 'field-user',
    ]);
    $fieldUserRole->forceFill(['active' => false])->save();

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);
    $secondAdmin = User::factory()->create();
    $secondAdmin->roles()->attach($adminRole);
    $fieldUser = User::factory()->create();
    $fieldUser->roles()->attach($fieldUserRole);

    $this->actingAs($admin);

    $component = Livewire::test(RolesIndex::class)
        ->assertSee('Administrador del sistema')
        ->assertSee('admin')
        ->assertSee('Activo')
        ->assertSee('Usuario de campo')
        ->assertSee('field-user')
        ->assertSee('Inactivo');

    $renderedText = preg_replace('/\s+/', ' ', strip_tags($component->html()));

    expect($renderedText)->toContain('admin 2 Activo')
        ->and($renderedText)->toContain('field-user 1 Inactivo');
});

it('searches roles by name and slug', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);
    Role::create([
        'name' => 'Regional coordinator',
        'slug' => 'regional-coordinator',
    ]);
    Role::create([
        'name' => 'Community operator',
        'slug' => 'community-operator',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->set('search', 'Regional')
        ->assertSee('Regional coordinator')
        ->assertDontSee('Community operator')
        ->set('search', 'community-operator')
        ->assertSee('Community operator')
        ->assertDontSee('Regional coordinator');
});

it('paginates roles by twenty per page', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    foreach (range(1, 21) as $number) {
        Role::create([
            'name' => sprintf('Listed role %02d', $number),
            'slug' => sprintf('listed-role-%02d', $number),
        ]);
    }

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->assertSee('Listed role 01')
        ->assertDontSee('Listed role 21')
        ->call('setPage', 2)
        ->assertSee('Listed role 21')
        ->assertDontSee('Listed role 01');
});

it('allows admin to open the create role form', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->assertSet('showCreateModal', true)
        ->assertSee('Nuevo rol');
});

it('creates an active role with a generated slug and no permissions', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->set('name', 'Coordinación de Campo')
        ->assertSet('slug', 'coordinacion-de-campo')
        ->call('saveRole')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false)
        ->assertSet('name', '')
        ->assertSet('slug', '')
        ->assertSet('selectedPermissions', [])
        ->assertSee('El rol se creó correctamente.')
        ->assertSee('coordinacion-de-campo')
        ->assertSee('Activo');

    $role = Role::query()
        ->withCount('users')
        ->where('slug', 'coordinacion-de-campo')
        ->firstOrFail();

    expect($role->active)->toBeTrue()
        ->and($role->permissions)->toBeEmpty()
        ->and($role->users_count)->toBe(0);
});

it('requires a role name', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->call('saveRole')
        ->assertHasErrors(['name' => 'required']);
});

it('rejects a generated slug that already exists', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->set('name', 'Admin')
        ->call('saveRole')
        ->assertHasErrors(['slug' => 'unique']);

    expect(Role::query()->where('slug', 'admin')->count())->toBe(1);
});

it('groups existing permissions and synchronizes multiple selections', function () {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);
    $surveyView = Permission::create([
        'name' => 'Consultar encuestas',
        'slug' => 'survey.view',
    ]);
    $surveyCreate = Permission::create([
        'name' => 'Crear encuestas',
        'slug' => 'survey.create',
    ]);
    $questionnaireView = Permission::create([
        'name' => 'Consultar cuestionarios',
        'slug' => 'questionnaire.view',
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);

    $this->actingAs($admin);

    $component = Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->assertSeeHtmlInOrder([
            'Cuestionarios',
            'Consultar cuestionarios',
            'Encuestas',
            'Crear encuestas',
            'Consultar encuestas',
        ])
        ->set('name', 'Survey Coordinator')
        ->set('selectedPermissions', [$surveyView->id, $surveyCreate->id, $questionnaireView->id])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::query()
        ->where('slug', 'survey-coordinator')
        ->firstOrFail();

    expect($role->permissions->modelKeys())->toEqualCanonicalizing([
        $surveyView->id,
        $surveyCreate->id,
        $questionnaireView->id,
    ]);
});

it('does not allow non-admin users to create roles', function (string $roleSlug) {
    $adminRole = Role::create([
        'name' => 'Administrator',
        'slug' => 'admin',
    ]);
    $userRole = Role::create([
        'name' => $roleSlug,
        'slug' => $roleSlug,
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach($adminRole);
    $user = User::factory()->create();
    $user->roles()->attach($userRole);

    $this->actingAs($admin);

    $component = Livewire::test(RolesIndex::class)
        ->call('openCreateModal')
        ->set('name', 'Unauthorized role');

    $this->actingAs($user);

    $component->call('saveRole')->assertForbidden();

    expect(Role::query()->where('slug', 'unauthorized-role')->exists())->toBeFalse();
})->with([
    'survey-admin',
    'field-user',
]);
