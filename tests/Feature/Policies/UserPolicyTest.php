<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new UserPolicy();

    $this->adminRole = Role::create([
        'name' => 'Administrador del sistema',
        'slug' => 'admin',
    ]);

    $this->surveyAdminRole = Role::create([
        'name' => 'Administrador de encuestas',
        'slug' => 'survey-admin',
    ]);

    $this->fieldUserRole = Role::create([
        'name' => 'Usuario de campo',
        'slug' => 'field-user',
    ]);
});

test('admin can access all user policy abilities', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach($this->adminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($admin))->toBeTrue()
        ->and($this->policy->view($admin, $target))->toBeTrue()
        ->and($this->policy->create($admin))->toBeTrue()
        ->and($this->policy->update($admin, $target))->toBeTrue()
        ->and($this->policy->activate($admin, $target))->toBeTrue();
});

test('survey admin cannot access user policy abilities without user permissions', function () {
    $user = User::factory()->create();
    $user->roles()->attach($this->surveyAdminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $target))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $target))->toBeFalse()
        ->and($this->policy->activate($user, $target))->toBeFalse();
});

test('field user cannot access user policy abilities without user permissions', function () {
    $user = User::factory()->create();
    $user->roles()->attach($this->fieldUserRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $target))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $target))->toBeFalse()
        ->and($this->policy->activate($user, $target))->toBeFalse();
});

test('user view permission allows viewing users', function () {
    $permission = Permission::create([
        'name' => 'Consultar usuarios',
        'slug' => 'user.view',
    ]);

    $this->surveyAdminRole->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($this->surveyAdminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $target))->toBeTrue()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $target))->toBeFalse()
        ->and($this->policy->activate($user, $target))->toBeFalse();
});

test('user create permission allows creating users', function () {
    $permission = Permission::create([
        'name' => 'Crear usuarios',
        'slug' => 'user.create',
    ]);

    $this->surveyAdminRole->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($this->surveyAdminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $target))->toBeFalse()
        ->and($this->policy->create($user))->toBeTrue()
        ->and($this->policy->update($user, $target))->toBeFalse()
        ->and($this->policy->activate($user, $target))->toBeFalse();
});

test('user update permission allows updating users', function () {
    $permission = Permission::create([
        'name' => 'Editar usuarios',
        'slug' => 'user.update',
    ]);

    $this->surveyAdminRole->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($this->surveyAdminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $target))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $target))->toBeTrue()
        ->and($this->policy->activate($user, $target))->toBeFalse();
});

test('user activate permission allows activating or deactivating users', function () {
    $permission = Permission::create([
        'name' => 'Activar o desactivar usuarios',
        'slug' => 'user.activate',
    ]);

    $this->surveyAdminRole->permissions()->attach($permission);

    $user = User::factory()->create();
    $user->roles()->attach($this->surveyAdminRole);

    $target = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $target))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $target))->toBeFalse()
        ->and($this->policy->activate($user, $target))->toBeTrue();
});
