<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin' => 'Administrador del sistema',
            'survey-admin' => 'Administrador de encuestas',
            'field-user' => 'Usuario de campo',
        ];

        foreach ($roles as $slug => $name) {
            Role::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $permissions = [
            'survey.view' => 'Consultar encuestas',
            'survey.create' => 'Crear encuestas',
            'survey.update' => 'Editar encuestas',
            'survey.complete' => 'Completar encuestas',
            'survey.cancel' => 'Cancelar encuestas',
            'questionnaire.view' => 'Consultar cuestionarios',
            'questionnaire.create' => 'Crear cuestionarios',
            'questionnaire.update' => 'Editar cuestionarios',
            'questionnaire.manage' => 'Administrar cuestionarios',
        ];

        foreach ($permissions as $slug => $name) {
            Permission::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }

        Role::where('slug', 'admin')->firstOrFail()->permissions()->sync([]);
        Role::where('slug', 'survey-admin')->firstOrFail()->permissions()->sync(
            Permission::whereIn('slug', [
                'survey.view',
                'questionnaire.view',
                'questionnaire.create',
                'questionnaire.update',
                'questionnaire.manage',
            ])->pluck('id')
        );
        Role::where('slug', 'field-user')->firstOrFail()->permissions()->sync(
            Permission::whereIn('slug', [
                'survey.view',
                'survey.create',
                'survey.update',
                'survey.complete',
                'survey.cancel',
            ])->pluck('id')
        );
    }
}
