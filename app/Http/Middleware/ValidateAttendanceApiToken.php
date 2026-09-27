<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAttendanceApiToken
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.attendance_api.token');

        if (! is_string($configured) || $configured === '') {
            return response()->json([
                'success' => false,
                'message' => 'Attendance API is not configured.',
            ], 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || $token === '' || ! hash_equals($configured, $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
