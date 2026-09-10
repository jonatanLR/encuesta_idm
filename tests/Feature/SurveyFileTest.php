<?php

use App\Models\Answer;
use App\Models\HouseholdMember;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\SurveyFile;
use App\Models\SurveyResponse;

it('relates a survey file to its survey response', function () {
    $response = SurveyResponse::factory()->create();

    $file = SurveyFile::create([
        'survey_response_id' => $response->id,
        'file_type' => 'image',
        'disk' => 'public',
        'path' => 'surveys/example.jpg',
        'original_name' => 'example.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    expect($file->surveyResponse->is($response))->toBeTrue();
    expect($response->surveyFiles->contains($file))->toBeTrue();
});

it('relates a survey file to its answer', function () {
    $response = SurveyResponse::factory()->create();

    $questionType = QuestionType::factory()->create([
        'code' => 'image',
    ]);

    $question = Question::factory()->create([
        'question_type_id' => $questionType->id,
    ]);

    $answer = Answer::factory()->create([
        'survey_response_id' => $response->id,
        'question_id' => $question->id,
    ]);

    $file = SurveyFile::create([
        'survey_response_id' => $response->id,
        'answer_id' => $answer->id,
        'file_type' => 'image',
        'disk' => 'public',
        'path' => 'surveys/example.jpg',
        'original_name' => 'example.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
    ]);

    expect($file->answer->is($answer))->toBeTrue();
    expect($answer->surveyFiles->contains($file))->toBeTrue();
});

it('can relate a survey file to a household member', function () {
    $response = SurveyResponse::factory()->create();
    $household = $response->household()->create();

    $relationship = \App\Models\HouseholdRelationship::factory()->create();

    $member = HouseholdMember::factory()->create([
        'household_id' => $household->id,
        'relationship_id' => $relationship->id,
    ]);

    $file = SurveyFile::create([
        'survey_response_id' => $response->id,
        'household_member_id' => $member->id,
        'file_type' => 'image',
        'disk' => 'public',
        'path' => 'surveys/member-dni.jpg',
        'original_name' => 'member-dni.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
    ]);

    expect($file->householdMember->is($member))->toBeTrue();
});
