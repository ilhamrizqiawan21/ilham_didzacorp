<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SecurityRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $limit = max(30, (int) config('security.rate_limit_per_minute', 180));
        $identity = $request->user()?->getAuthIdentifier() ?? $request->ip();
        $key = 'web:' . sha1($identity . '|' . $request->route()?->getName() . '|' . $request->method());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Terlalu banyak permintaan. Silakan coba lagi nanti.',
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);

        return $response;
    }
}
