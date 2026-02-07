<?php

use Coollabsio\LaravelSaas\Http\Controllers\InstanceSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified', 'root'])->group(function () {
    Route::get('settings/instance', [InstanceSettingsController::class, 'edit'])->name('instance-settings.edit');
    Route::patch('settings/instance', [InstanceSettingsController::class, 'update'])->name('instance-settings.update');
});
