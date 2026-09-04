<?php

use App\Livewire\CommunitySearch;
use App\Livewire\Survey\Start;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('/encuestas/iniciar', Start::class)->name('survey.start');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

/* Route::get('/debug', function () {
    return \App\Models\QuestionCondition::with([
    'question',
    'dependsOnQuestion',
    'dependsOnOption',
])->first();
}); */

/* Route::get('/debug', function () {
    $user = User::create([
    'name' => 'Usuario de Desarrollo',
    'email' => 'dev@encuestaidm.test',
    'password' => Hash::make('Dev12345!'),
]);

    return $user;
}); */

// Route::view('/community-test', 'community-search');
// Route::livewire('/community-test',CommunitySearch::class);
Route::view('/community-test', 'community-test');

require __DIR__.'/auth.php';
