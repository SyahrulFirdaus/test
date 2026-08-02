<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsCustomer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'customer' => EnsureUserIsCustomer::class,
        ]);

        // Area admin dan area pelanggan punya halaman masuk masing-masing,
        // jadi tamu diarahkan ke halaman yang sesuai dengan tujuannya.
        $middleware->redirectGuestsTo(
            fn ($request) => $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login')
        );

        // Pengguna yang sudah masuk tidak perlu melihat halaman login lagi.
        $middleware->redirectUsersTo(
            fn ($request) => $request->user()?->isAdmin()
                ? route('admin.dashboard')
                : route('dashboard')
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
