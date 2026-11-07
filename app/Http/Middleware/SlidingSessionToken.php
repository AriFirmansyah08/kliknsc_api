<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;


class SlidingSessionToken
{
    private const IDLE_MINUTES = 5;

    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->forceFill([
                'expires_at' => now()->addMinutes(self::IDLE_MINUTES),
            ])->save();
        }

        return $next($request);
    }
}