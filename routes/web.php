<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('welcome', function () {
    return view('welcome');
})->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');


// Route d'inscription sur invitation (PUBLIC)
Volt::route('register/{token}', 'auth.register-invitee')
    //->middleware(['guest', 'check.invitation'])
    ->name('register.invitee');


Route::middleware(['auth', 'verified'])->group(function () {

    Volt::route('', 'dashboard.index')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('utilisateurs')->name('users.')->group(function () {
            Volt::route('', 'admin.users.index')->name('index');
            Volt::route('invitations', 'admin.users.invitations')->name('invitations');
            Volt::route('rôles-permissions', 'admin.users.roles-permissions')->name('roles-permissions');
        });

        Route::prefix('roles')->name('roles.')->group(function () {
            Volt::route('', 'admin.roles.index')->name('index');
        });
    });

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
