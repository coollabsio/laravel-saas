<?php

use Coollabsio\LaravelSaas\Http\Controllers\BillingController;
use Coollabsio\LaravelSaas\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('settings/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

Route::middleware('web')->group(function () {
    Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');
});
