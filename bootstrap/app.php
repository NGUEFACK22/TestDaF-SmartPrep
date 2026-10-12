<?php

use App\Exceptions\ExamException;
use App\Http\Middleware\EnsureRole;
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
        // Middleware de rôles (candidat / admin / correcteur).
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
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

        // DIAGNOSTIC TEMPORAIRE : affiche l'erreur réelle dans l'environnement Render
        // (détecté via SESSION_DRIVER=database, indépendant de APP_DEBUG qui arrive vide).
        $exceptions->render(function (Throwable $e, $request) {
            if (env('SESSION_DRIVER') === 'database' && ! $e instanceof ExamException) {
                return response("<pre>ERROR: ".get_class($e)."\n".$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n</pre>", 500);
            }

            return null;
        });
    })->create();
