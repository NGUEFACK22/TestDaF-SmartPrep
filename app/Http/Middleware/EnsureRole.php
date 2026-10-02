<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureRole — vérifie que l'utilisateur connecté possède l'un des rôles requis.
 * Usage : Route::middleware('role:admin') ou 'role:admin,corrector'.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->slug, $roles, true)) {
            abort(403, 'Accès refusé : privilèges insuffisants.');
        }

        return $next($request);
    }
}
