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
            \Illuminate\Support\Facades\Log::debug('[Admin404] handler invoked', [
                'path'           => $request->path(),
                'auth_check'     => auth()->check(),
                'user_id'        => auth()->id(),
            ]);

            if (!preg_match('#^admin/[^/]+(/?$)#', $request->path())) {
                \Illuminate\Support\Facades\Log::debug('[Admin404] path no coincideix — ignorat');
                return null;
            }
            if (!auth()->check()) {
                \Illuminate\Support\Facades\Log::debug('[Admin404] usuari no autenticat — ignorat');
                return null;
            }

            $user  = auth()->user();
            $colla = $user->colla_id ? \App\Models\Colla::find($user->colla_id) : null;
            $colla ??= $user->hasRole('super-admin') ? \App\Models\Colla::first() : null;

            \Illuminate\Support\Facades\Log::debug('[Admin404] resolució', [
                'user_id'       => $user->id,
                'user_colla_id' => $user->colla_id,
                'colla_trobada' => $colla?->shortname ?? 'NULL',
                'redirect_to'   => $colla ? "/admin/{$colla->shortname}" : 'cap',
            ]);

            return $colla ? redirect("/admin/{$colla->shortname}") : null;
        });
    })->create();
