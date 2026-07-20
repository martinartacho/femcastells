<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*', 'api-fempinya/*'),
        );

        // Tenant no trobat (colla eliminada o cookie vella) → redirigeix al tenant vàlid
        // Filament fa abort(404) → NotFoundHttpException, no ModelNotFoundException
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            Request $request
        ) {
            // Només interceptem /admin/{shortname} i /admin/{shortname}/ (no sub-pàgines)
            if (!preg_match('#^admin/[^/]+(/?$)#', $request->path())) return null;
            if (!auth()->check()) return null;

            $user  = auth()->user();
            $colla = $user->colla_id ? \App\Models\Colla::find($user->colla_id) : null;
            $colla ??= $user->hasRole('super-admin') ? \App\Models\Colla::first() : null;

            return $colla ? redirect("/admin/{$colla->shortname}") : null;
        });
    })->create();
