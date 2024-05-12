<?php

use Illuminate\Support\Facades\Route;
use ShipSaasInboxProcess\Http\Controllers\InboxController;

if (config('inbox.uses_default_inbox_route')) {
    $routePath = config('inbox.route_path', 'inbox');

    Route::post($routePath . '/{topic}', [InboxController::class, 'handle'])
        ->name('inbox.topic');
}
