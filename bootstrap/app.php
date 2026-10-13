<?php

use App\Exceptions\ExamException;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware de rôles (candidat / admin / correcteur).
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        // Confiance aux proxies de reverse-proxy (Render) pour générer des
        // URLs https correctes (sans APP_URL, le conteneur voit du http).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Rendu des erreurs métier du moteur d'examen (verrouillage / autorisation).
        $exceptions->render(function (ExamException $e, $request) {
            $status = $e->getCode() >= 400 ? (int) $e->getCode() : 422;

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'locked' => in_array($status, [403, 423], true),
                    'reason' => $e->reason(),
                    'message' => $e->getMessage(),
                ], $status);
            }

            return back()->with('error', $e->getMessage());
        });
    })->create();
