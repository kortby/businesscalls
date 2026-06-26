<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSupervisor()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Forbidden. Supervisor permissions required.'], 403);
            }

            abort(403, 'Forbidden. Supervisor permissions required.');
        }

        return $next($request);
    }
}
