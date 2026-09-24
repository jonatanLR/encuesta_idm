<?php

use App\Livewire\CommunitySearch;
use App\Livewire\Survey\Form;
use App\Livewire\Survey\Index;
use App\Livewire\SurveyManagement\Index as SurveyManagementIndex;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;


Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('/encuestas', Index::class)->name('survey.index');
    Route::livewire('/encuestas/{response}', Form::class)->name('survey.form');
    Route::livewire('/administrar-encuestas', SurveyManagementIndex::class)->name('survey-management.index');
    Route::livewire('/administrar-encuestas/comunidades', \App\Livewire\SurveyManagement\Communities\Index::class)
        ->name('survey-management.communities');
    Route::livewire('/administrar-encuestas/{questionnaire}/versiones', \App\Livewire\SurveyManagement\Versions\Index::class)
        ->name('survey-management.versions');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});


Route::view('/community-test', 'community-test');

require __DIR__ . '/auth.php';
