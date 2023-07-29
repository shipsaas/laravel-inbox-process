<?php

use Illuminate\Support\Facades\Route;
use ShipSaasInboxProcess\Http\Controllers\InboxController;

Route::post('inbox/{topic}', [InboxController::class, 'handle']);
