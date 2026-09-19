<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! config('security.force_password_change', false) || ! $user->is_password_default) {
            return $next($request);
        }

        $role = $user->role?->nama_role;

        // Students may continue using the default password. Password changes
        // remain available from the account settings page, but are no longer
        // mandatory for the siswa role.
        if ($role === 'siswa') {
            return $next($request);
        }

        if (
            $request->routeIs('*.pengaturan-akun') ||
            $request->routeIs('*.pengaturan') ||
            $request->routeIs('*.pengaturan.update') ||
            $request->routeIs('*.profil') ||
            $request->routeIs('*.profil.update') ||
            $request->routeIs('login') ||
            $request->routeIs('login.post') ||
            $request->routeIs('logout')
        ) {
            return $next($request);
        }

        $route = match ($role) {
            'admin' => 'admin.pengaturan-akun',
            'guru' => 'guru.pengaturan',
            'kepala_sekolah' => 'kepsek.pengaturan',
            default => null,
        };

        if ($route && app('router')->has($route)) {
            return redirect()->route($route)->with('warning', 'Demi keamanan, silakan ubah password default sebelum melanjutkan.');
        }

        return $next($request);
    }
}
