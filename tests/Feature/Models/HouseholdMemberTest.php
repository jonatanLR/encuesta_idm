<?php

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use App\Models\SurveyResponse;
use Illuminate\Support\Carbon;

it('persists a member with a null capture start timestamp', function () {
    $household = Household::create([
        'survey_response_id' => SurveyResponse::factory()->create()->id,
    ]);

    $relationship = HouseholdRelationship::create([
        'code' => 'HEAD',
        'name' => 'Head of household',
        'active' => true,
        'sort_order' => 1,
    ]);

    $householdMember = HouseholdMember::create([
        'household_id' => $household->id,
        'relationship_id' => $relationship->id,
        'name' => 'Maria Lopez',
    ]);

    $householdMember->refresh();

    $this->assertDatabaseHas('household_members', [
        'id' => $householdMember->id,
        'capture_started_at' => null,
    ]);

    expect($householdMember->capture_started_at)->toBeNull();
});

it('casts a member capture start timestamp as datetime', function () {
    $household = Household::create([
        'survey_response_id' => SurveyResponse::factory()->create()->id,
    ]);

    $relationship = HouseholdRelationship::create([
        'code' => 'HEAD',
        'name' => 'Head of household',
        'active' => true,
        'sort_order' => 1,
    ]);

    $captureStartedAt = Carbon::create(2026, 9, 9, 14, 30, 0);

    $householdMember = HouseholdMember::create([
        'household_id' => $household->id,
        'relationship_id' => $relationship->id,
        'name' => 'Maria Lopez',
    ]);
    $householdMember->capture_started_at = $captureStartedAt;
    $householdMember->save();

    $householdMember->refresh();

    expect($householdMember->capture_started_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($householdMember->capture_started_at->equalTo($captureStartedAt))
        ->toBeTrue();
});
