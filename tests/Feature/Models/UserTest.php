<?php

use App\Models\SurveyResponse;
use App\Models\User;

it('has many survey responses', function () {
    $user = User::factory()->create();

    $response = SurveyResponse::factory()->create([
        'created_by' => $user->id,
    ]);

    expect($user->surveyResponses)
        ->toHaveCount(1)
        ->and($user->surveyResponses->first())
        ->toBeInstanceOf(SurveyResponse::class)
        ->and($user->surveyResponses->first()->id)
        ->toBe($response->id);
});

//------------------------------------------------

it('generates user initials', function () {
    $user = User::factory()->create([
        'name' => 'Juan Pérez',
    ]);

    expect($user->initials())
        ->toBe('JP');
});

//-----------------------------------------------

it('casts email_verified_at as a date', function () {
    $verifiedAt = now();

    $user = User::factory()->create([
        'email_verified_at' => $verifiedAt,
    ]);

    $user->refresh();

    expect($user->email_verified_at)
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

//----------------------------------------------

it('hashes the password', function () {
    $password = 'password-secreta';

    $user = User::factory()->create([
        'password' => $password,
    ]);

    expect($user->password)
        ->not->toBe($password)
        ->and(\Illuminate\Support\Facades\Hash::check($password, $user->password))
        ->toBeTrue();
});

//-----------------------------------------------

it('hides sensitive attributes', function () {
    $user = User::factory()->create();

    $attributes = $user->toArray();

    expect($attributes)
        ->not->toHaveKey('password')
        ->and($attributes)
        ->not->toHaveKey('remember_token');
});

