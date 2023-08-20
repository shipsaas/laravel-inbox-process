<?php

use Illuminate\Support\Facades\Route;
use ShipSaasInboxProcess\Http\Controllers\InboxController;

if (config('inbox.uses_default_inbox_route')) {
    Route::post('inbox/{topic}', [InboxController::class, 'handle'])
        ->name('inbox.topic');
}
