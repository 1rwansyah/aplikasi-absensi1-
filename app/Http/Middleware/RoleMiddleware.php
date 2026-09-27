<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        return redirect()
            ->to($user->homeUrl())
            ->with('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    }
}
