<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika user adalah admin, lanjutkan request
        if ($request->user() && $request->user()->akses === "admin") {
            return $next($request);
        }

        // Jika bukan admin, tampilkan pesan error lalu redirect ke dashboard
        return response()->view('errors.403_admin');
    }
}
