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
            'community.view' => 'Consultar comunidades',
            'community.create' => 'Crear comunidades',
            'community.update' => 'Editar comunidades',
            'community.activate' => 'Activar o desactivar comunidades',
            'section.view' => 'Consultar secciones',
            'section.create' => 'Crear secciones',
            'section.update' => 'Editar secciones',
            'section.activate' => 'Activar o desactivar secciones',
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
                'community.view',
                'community.create',
                'community.update',
                'community.activate',
                'section.view',
                'section.create',
                'section.update',
                'section.activate',
                'question.view',
                'question.create',
                'question.update',
                'question.activate',
                'question-option.view',
                'question-option.create',
                'question-option.update',
                'question-option.activate',
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
