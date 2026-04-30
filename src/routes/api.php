<?php

use App\Http\Controllers\NotificationController;
use App\Http\Middleware\VerifyNotificationIngress;
use Illuminate\Support\Facades\Route;

Route::post('/notifications', NotificationController::class)
    ->middleware(VerifyNotificationIngress::class);
