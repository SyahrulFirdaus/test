<?php

use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsBusiness;
use App\Http\Middleware\EnsureUserIsCustomer;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
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
            // Hak akses menu Admin per akun: ->middleware('admin.permission:penawaran').
            'admin.permission' => EnsureAdminPermission::class,
            'superadmin' => EnsureUserIsSuperAdmin::class,
            'customer' => EnsureUserIsCustomer::class,
            'business' => EnsureUserIsBusiness::class,
        ]);

        // Area admin dan area pelanggan punya halaman masuk masing-masing,
        // jadi tamu diarahkan ke halaman yang sesuai dengan tujuannya.
        $middleware->redirectGuestsTo(
            fn ($request) => $request->is('admin', 'admin/*', 'superadmin', 'superadmin/*')
                ? route('admin.login')
                : route('login')
        );

        // Pengguna yang sudah masuk tidak perlu melihat halaman login lagi.
        $middleware->redirectUsersTo(
            fn ($request) => match (true) {
                (bool) $request->user()?->isSuperAdmin() => route('superadmin.dashboard'),
                (bool) $request->user()?->isAdmin() => route('admin.dashboard'),
                default => route('dashboard'),
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
