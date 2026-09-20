<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $isBlocked = BlockedIp::query()
                ->active()
                ->where('ip_address', $ip)
                ->exists();

            if ($isBlocked) {
                return response(
                    'Akses dari IP ini sedang diblokir. Silakan hubungi administrator.',
                    403
                );
            }
        } catch (Throwable $exception) {
            // Do not fail open when the blocklist cannot be checked. A
            // temporary 503 is safer than allowing every blocked IP through.
            Log::critical('Pemeriksaan IP terblokir tidak tersedia.', [
                'exception' => get_class($exception),
            ]);

            return response('Layanan keamanan sedang tidak tersedia. Silakan coba lagi nanti.', 503);
        }

        return $next($request);
    }
}
