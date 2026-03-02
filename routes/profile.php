<?php

use Coollabsio\LaravelSaas\Http\Controllers\EmailChangeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::post('settings/email', [EmailChangeController::class, 'store'])
        ->name('email-change.store');
});

Route::middleware(['web'])->group(function () {
    Route::get('email/verify-change', [EmailChangeController::class, 'verify'])
        ->name('email-change.verify')
        ->middleware('signed');
});
