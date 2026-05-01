<?php

use App\Http\Controllers\DiscordController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\VerifyNotificationIngress;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyNotificationIngress::class)->group(function (): void {
    // Уведомление в Telegram (через бота, токен/чат ID берутся из конфигов hub'а).
    Route::post('/notifications', NotificationController::class);

    // Уведомление в Discord. Backend присылает webhook_url гильдии и сообщение,
    // hub проксирует POST на этот webhook от своего IP.
    Route::post('/discord', DiscordController::class);
});
