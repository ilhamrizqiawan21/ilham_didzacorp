<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        try {
            $blockedIp = BlockedIp::query()
                ->where('ip_address', $ip)
                ->first();

            if ($blockedIp && $blockedIp->blocked_until->isPast()) {
                $blockedIp->delete();
                $blockedIp = null;
            }

            if ($blockedIp) {
                return response(
                    'Akses dari IP ini sedang diblokir. Silakan hubungi administrator.',
                    403
                );
            }
        } catch (Throwable) {
            return $next($request);
        }

        return $next($request);
    }
}
