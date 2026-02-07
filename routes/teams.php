<?php

use Coollabsio\LaravelSaas\Http\Controllers\TeamController;
use Coollabsio\LaravelSaas\Http\Controllers\TeamInvitationController;
use Coollabsio\LaravelSaas\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('settings/team', [TeamController::class, 'edit'])->name('teams.edit');
    Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::put('teams/{team}/switch', [TeamController::class, 'switchTeam'])->name('teams.switch');

    Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('team-invitations.store');
    Route::delete('teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('team-invitations.destroy');

    Route::delete('teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('team-members.destroy');
});

Route::middleware('web')->group(function () {
    Route::get('invitations/{token}', [TeamInvitationController::class, 'accept'])->name('team-invitations.accept');
    Route::post('invitations/{token}', [TeamInvitationController::class, 'process'])
        ->middleware('auth')
        ->name('team-invitations.process');
});
