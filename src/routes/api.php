<?php

use App\Http\Controllers\TelegramLogController;
use App\Http\Middleware\VerifyTelegramLogIngress;
use Illuminate\Support\Facades\Route;

Route::post('/telegram-log', TelegramLogController::class)
    ->middleware(VerifyTelegramLogIngress::class);

