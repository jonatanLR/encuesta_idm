<?php

namespace Tests\Unit\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Policies\SurveyResponsePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyResponsePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithPermission(string $roleSlug, string $permissionSlug): User
    {
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

    public function test_user_with_survey_view_can_view_any_response(): void
    {
        $user = $this->createUserWithPermission('field-user', 'survey.view');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->view($user, $response));
    }

    public function test_user_without_survey_view_cannot_view_response(): void
    {
        $user = User::factory()->create();
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->view($user, $response));
    }

    public function test_admin_can_update_any_response(): void
    {
        $user = $this->createUserWithPermission('admin', 'survey.update');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->update($user, $response));
    }

    public function test_survey_admin_cannot_update_response(): void
    {
        $user = $this->createUserWithPermission('survey-admin', 'survey.update');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->update($user, $response));
    }

    public function test_field_user_can_update_own_response(): void
    {
        $user = $this->createUserWithPermission('field-user', 'survey.update');

        $response = SurveyResponse::factory()->create([
            'created_by' => $user->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->update($user, $response));
    }

    public function test_field_user_cannot_update_another_users_response(): void
    {
        $owner = User::factory()->create();

        $user = $this->createUserWithPermission('field-user', 'survey.update');

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->update($user, $response));
    }

    public function test_admin_can_complete_any_response(): void
    {
        $user = $this->createUserWithPermission('admin', 'survey.complete');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->complete($user, $response));
    }

    public function test_survey_admin_cannot_complete_response(): void
    {
        $user = $this->createUserWithPermission('survey-admin', 'survey.complete');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->complete($user, $response));
    }

    public function test_field_user_can_complete_own_response(): void
    {
        $user = $this->createUserWithPermission('field-user', 'survey.complete');

        $response = SurveyResponse::factory()->create([
            'created_by' => $user->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->complete($user, $response));
    }

    public function test_field_user_cannot_complete_another_users_response(): void
    {
        $owner = User::factory()->create();

        $user = $this->createUserWithPermission('field-user', 'survey.complete');

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->complete($user, $response));
    }

    public function test_admin_can_cancel_any_response(): void
    {
        $user = $this->createUserWithPermission('admin', 'survey.cancel');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->cancel($user, $response));
    }

    public function test_survey_admin_cannot_cancel_response(): void
    {
        $user = $this->createUserWithPermission('survey-admin', 'survey.cancel');
        $response = SurveyResponse::factory()->create();

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->cancel($user, $response));
    }

    public function test_field_user_can_cancel_own_response(): void
    {
        $user = $this->createUserWithPermission('field-user', 'survey.cancel');

        $response = SurveyResponse::factory()->create([
            'created_by' => $user->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertTrue($policy->cancel($user, $response));
    }

    public function test_field_user_cannot_cancel_another_users_response(): void
    {
        $owner = User::factory()->create();

        $user = $this->createUserWithPermission('field-user', 'survey.cancel');

        $response = SurveyResponse::factory()->create([
            'created_by' => $owner->id,
        ]);

        $policy = new SurveyResponsePolicy();

        $this->assertFalse($policy->cancel($user, $response));
    }
}
