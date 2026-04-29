<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Env;

// Ensure .env is loaded (required for APP_KEY, DB creds, etc).
// Some minimal installs / custom bootstraps may not load it automatically.
foreach (['APP_KEY'] as $key) {
    // If the variable exists but is empty, Dotenv won't override it.
    // Unset it so it can be populated from .env.
    if (\getenv($key) === '') {
        \putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
\Dotenv\Dotenv::create(Env::getRepository(), dirname(__DIR__))->safeLoad();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
