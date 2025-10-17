<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRegistration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.allow_new_user')) {
            return response()->json([
                'message' => 'Esta deshabilitada la creación de cuentas por el momento',
                'errors' => [
                    'registro' => ['Esta deshabilitada la creación de cuentas por el momento']
                ]
            ], 403);
        }

        return $next($request);
    }
}
