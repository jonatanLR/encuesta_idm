<?php

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\HouseholdRelationship;
use App\Models\SurveyResponse;
use App\Services\HouseholdMemberService;
use Illuminate\Support\Carbon;

it('starts capture for an active member and persists the timestamp', function () {
    $response = SurveyResponse::factory()->create();
    $household = Household::create([
        'survey_response_id' => $response->id,
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
    $captureStartedAt = Carbon::create(2026, 9, 9, 14, 30, 0);
    $this->travelTo($captureStartedAt);

    $result = app(HouseholdMemberService::class)->startCapture(
        $response,
        $householdMember
    );

    expect($result->capture_started_at->equalTo($captureStartedAt))->toBeTrue();
    $this->assertDatabaseHas('household_members', [
        'id' => $householdMember->id,
        'capture_started_at' => $captureStartedAt->toDateTimeString(),
    ]);
});

it('preserves the existing capture timestamp', function () {
    $response = SurveyResponse::factory()->create();
    $household = Household::create([
        'survey_response_id' => $response->id,
    ]);
    $relationship = HouseholdRelationship::create([
        'code' => 'HEAD',
        'name' => 'Head of household',
        'active' => true,
        'sort_order' => 1,
    ]);
    $captureStartedAt = Carbon::create(2026, 9, 8, 10, 15, 0);
    $householdMember = HouseholdMember::create([
        'household_id' => $household->id,
        'relationship_id' => $relationship->id,
        'name' => 'Maria Lopez',
    ]);
    $householdMember->capture_started_at = $captureStartedAt;
    $householdMember->save();
    $this->travelTo(Carbon::create(2026, 9, 9, 14, 30, 0));

    $result = app(HouseholdMemberService::class)->startCapture(
        $response,
        $householdMember
    );

    expect($result->capture_started_at->equalTo($captureStartedAt))->toBeTrue();
    $this->assertDatabaseHas('household_members', [
        'id' => $householdMember->id,
        'capture_started_at' => $captureStartedAt->toDateTimeString(),
    ]);
});

it('rejects a member from another survey response without starting capture', function () {
    $response = SurveyResponse::factory()->create();
    $otherResponse = SurveyResponse::factory()->create();
    $household = Household::create([
        'survey_response_id' => $otherResponse->id,
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

    expect(fn () => app(HouseholdMemberService::class)->startCapture(
        $response,
        $householdMember
    ))->toThrow(InvalidArgumentException::class);
    $this->assertDatabaseHas('household_members', [
        'id' => $householdMember->id,
        'capture_started_at' => null,
    ]);
});

it('rejects a soft-deleted member without starting capture', function () {
    $response = SurveyResponse::factory()->create();
    $household = Household::create([
        'survey_response_id' => $response->id,
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
    $householdMember->delete();

    expect(fn () => app(HouseholdMemberService::class)->startCapture(
        $response,
        $householdMember
    ))->toThrow(InvalidArgumentException::class);
    $this->assertDatabaseHas('household_members', [
        'id' => $householdMember->id,
        'capture_started_at' => null,
    ]);
});
